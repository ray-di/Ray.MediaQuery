---
layout: default
title: BDR パターン集
description: 行ごとの加工（factory 属性）と結果セット全体の成形（PostQueryInterface）の使い分け。バッジ、enum、JOIN グルーピング、ソート、SPL イテレータ、Null Object。
lang: ja
permalink: /tutorial/bdr-patterns/ja/
---

# BDR パターン集

[English]({{ '/tutorial/bdr-patterns/' | relative_url }}) | [ハンズオンチュートリアル]({{ '/tutorial/ja/' | relative_url }})

Ray.MediaQuery には、SQL の結果をオブジェクトに変える機構が2つある。どちらを使うかで「できること」が変わる。

| 機構 | 渡されるもの | 形 | 用途 |
|---|---|---|---|
| **`factory:` 属性** | 行ごとに1回、カラムを引数で | 1行 → 1オブジェクト | 行の加工・enrichment |
| **`PostQueryInterface`** | 結果セット全体 | N行 → 1オブジェクト | 集約・コレクション・成形 |

第1部は `factory:`、第2部は `PostQueryInterface`。混同するとコードは動かない。

---

## 第1部 — `factory:` 行ごとの加工

ファクトリは**1行につき1回**呼ばれ、SELECT のカラムが順番に引数で渡る。戻り値が行の数だけ並ぶ。チュートリアル第7章の `ArticleStatsFactory` がこれ。

> 対応づけは**名前ではなく位置**（`PDO::FETCH_FUNC`）。ファクトリのシグネチャは SELECT のカラム列と順序・個数を 1:1 で合わせる必要がある。引数を減らしても「その名前のカラムが選ばれる」わけではなく、先頭から順に詰められるだけ。以下の各例は、SELECT がそのファクトリの宣言どおりのカラムを返す前提。

```php
interface ArticleQueryInterface
{
    /** @return list<Article> */
    #[DbQuery('article_list', factory: ArticleFactory::class)]
    public function list(): array;
}
```

### テンプレートの `if` が消える

```twig
{# よくある光景 #}
{% if article.status == 'published' and article.publishedAt <= now %}
    <span class="badge">公開中</span>
{% elseif article.status == 'draft' %}
    <span class="badge badge--draft">下書き</span>
{% endif %}
```

ステータス判定がテンプレートに染み出している。ファクトリで解決する。

```php
final class ArticleFactory
{
    public function factory(int $id, string $title, string $status): Article
    {
        $badge = match($status) {
            'published' => 'badge',
            'draft'     => 'badge badge--draft',
            default     => '',
        };

        return new Article(id: $id, title: $title, status: $status, badge: $badge);
    }
}
```

```twig
{# テンプレートは表示するだけ #}
<span class="{{ article.badge }}">{{ article.status }}</span>
```

### 文字列カラムを enum で受け取る

DB の `status` は文字列。ファクトリで PHP の enum に変換すれば、型安全な比較になる。

```php
public function factory(int $id, string $status): Article
{
    return new Article(id: $id, status: Status::from($status));
}
```

```twig
{# 文字列比較ではなく enum 比較 #}
{% if article.status == enum('App\\Status::Published') %}...{% endif %}
```

タイプミスは `Status::from()` の時点で例外になる。テンプレートに生の文字列が散らばらない。

### 表示用の値をエンティティに乗せる

「本文の文字数から読了時間を出したい」「価格をカンマ区切りで表示したい」。ファクトリで計算して乗せる。

```php
public function factory(int $id, string $body, int $priceYen): Article
{
    return new Article(
        id: $id,
        readingMinutes: (int) ceil(mb_strlen($body) / 400),
        priceFormatted: number_format($priceYen) . '円',
    );
}
```

```twig
{{ article.readingMinutes }}分で読めます
{{ article.priceFormatted }}
```

テンプレートに計算式がない。テストもファクトリ単体で書ける。

### 現在時刻・現在ユーザーを注入する

ファクトリはコンストラクタで DI を受け取れる（第8章の `age` と同じ）。SQL のカラムにない値を、外から注入した依存で組み立てる。

```php
final class ArticleFactory
{
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly DateTimeInterface $now,
    ) {}

    public function factory(int $id, int $authorId, string $publishedAt): Article
    {
        return new Article(
            id: $id,
            isOwn: $authorId === $this->currentUser->id(),
            isNew: (new DateTimeImmutable($publishedAt)) > DateTimeImmutable::createFromInterface($this->now)->modify('-7 days'),
        );
    }
}
```

```twig
{% if article.isOwn %}<a href="/edit/{{ article.id }}">編集</a>{% endif %}
{% if article.isNew %}<span class="new">NEW</span>{% endif %}
```

`CurrentUserInterface` は Ray.Di で bind する。テストでは `FakeCurrentUser` を差し込む。`DateTimeInterface` は `MediaQueryModule` が既に bind 済み。

---

## 第2部 — `PostQueryInterface` 結果セット全体の成形

行をまたぐ処理（グルーピング、ソート、絞り込み、空判定）は `factory:` ではできない。`factory:` は1行ずつしか見ないからだ。結果セット全体が要るときは `PostQueryInterface` を使う。クラスは戻り値型として宣言し、`fromContext()` に `$context->rows`（全行）が渡る。チュートリアル第12章の `ArticleSearchResult` がこれ。

```php
interface ArticleQueryInterface
{
    #[DbQuery('article_with_comments')]
    public function withComments(): Articles;   // Articles implements PostQueryInterface
}
```

### フラットな JOIN 結果を親子に組み立てる

JOIN の結果は平らな行で返る。コメントを記事ごとにまとめるのは、テンプレートでもコントローラーでも面倒。`fromContext()` でまとめる。

```sql
SELECT a.id, a.title, c.id AS comment_id, c.body AS comment_body
FROM article a
LEFT JOIN comment c ON c.article_id = a.id
ORDER BY a.id
```

```php
final class Articles implements PostQueryInterface
{
    /** @param list<Article> $items */
    public function __construct(public readonly array $items) {}

    public static function fromContext(PostQueryContext $context): static
    {
        $titles = [];
        $comments = [];
        foreach ($context->rows as $row) {
            $id = $row['id'];
            $titles[$id] ??= $row['title'];
            if ($row['comment_id'] !== null) {
                $comments[$id][] = new Comment((int) $row['comment_id'], (string) $row['comment_body']);
            }
        }

        $items = [];
        foreach ($titles as $id => $title) {
            $items[] = new Article((int) $id, (string) $title, $comments[$id] ?? []);
        }

        return new static($items);
    }
}
```

```twig
{% for article in articles.items %}
  <h2>{{ article.title }}</h2>
  {% for comment in article.comments %}<p>{{ comment.body }}</p>{% endfor %}
{% endfor %}
```

1クエリでネストしたオブジェクトが返る。N+1 も、コントローラーでの手動グルーピングもない。

### ソートは `fromContext()` で

SQL の `ORDER BY` では届かない並びがある。`ORDER BY name` は辞書順なので `item1, item10, item2` になる。全行が揃ってから並べ替える。

```php
final class FileList implements PostQueryInterface
{
    /** @param list<File> $files */
    public function __construct(public readonly array $files) {}

    public static function fromContext(PostQueryContext $context): static
    {
        $names = [];
        foreach ($context->rows as $row) {
            $names[(int) $row['id']] = (string) $row['name'];
        }
        natsort($names);                       // item1, item2, item10

        $files = [];
        foreach ($names as $id => $name) {
            $files[] = new File($id, $name);
        }

        return new static($files);
    }
}
```

業務固有の優先順位（`news → feature → opinion`）も同じ場所に書ける。`usort()` に `['news' => 0, 'feature' => 1, 'opinion' => 2]` を引かせるだけ。テンプレートは並び順を意識しない。

### SPL イテレータでフィルタ・制限する

`PostQueryInterface` と `IteratorAggregate` を一緒に実装すると、「公開済みだけ、最大20件」のような絞り込みをコレクション自身に閉じ込められる。テンプレートで毎回 `{% if %}` を書かなくて済む。

```php
final class Posts implements PostQueryInterface, IteratorAggregate
{
    /** @param list<Post> $items */
    public function __construct(private readonly array $items) {}

    public static function fromContext(PostQueryContext $context): static
    {
        $items = [];
        foreach ($context->rows as $row) {
            $items[] = new Post((int) $row['id'], (string) $row['title'], (bool) $row['is_published']);
        }

        return new static($items);
    }

    public function getIterator(): Traversable
    {
        return new LimitIterator(
            new CallbackFilterIterator(
                new ArrayIterator($this->items),
                static fn (Post $p) => $p->isPublished,
            ),
            0,
            20,
        );
    }
}
```

```twig
{% for post in posts %}
    {# 下書きはここに来ない。21件目以降も来ない #}
    {{ post.title }}
{% endfor %}
```

`SplPriorityQueue` を使えば「ピン留めを先頭に、残りは日付順」も同じ場所に書ける。

### Null Object — クエリは常に完成したエンティティを返す

行が見つからないと、`type: 'row'` のクエリは `null` を返す。テンプレートに `{% if profile %}` が増える原因。`fromContext()` で空を判定し、常に完成したエンティティを返す。

```php
final class UserProfile implements PostQueryInterface
{
    public function __construct(
        public readonly string $displayName,
        public readonly string $avatarUrl,
        public readonly bool $isGuest,
    ) {}

    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? null;
        if ($row === null) {
            return new static('Guest', '/img/guest.png', true);   // プレースホルダ
        }

        return new static((string) $row['name'], (string) $row['avatar_url'], false);
    }
}
```

```twig
{# 存在チェック不要。avatarUrl は常に値がある #}
<img src="{{ profile.avatarUrl }}">
<span>{{ profile.displayName }}</span>
```

クエリの戻り値はもう `UserProfile|null` ではなく `UserProfile`。テンプレートは分岐しない。

---

> 第1部と第2部に共通することが一つある。**テンプレートはプロパティを読むだけ**。判定も計算も並び順も空判定も、クエリ境界で終わっている。違うのは「1行を加工するか、結果セット全体を成形するか」だけ。
