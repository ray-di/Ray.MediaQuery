---
layout: default
title: Ray.MediaQuery ハンズオンチュートリアル
description: ブログサービスを題材に、Ray.MediaQuery 1.1.0 までの主要機能を13章で体験する入門
lang: ja
permalink: /tutorial/
---

# Ray.MediaQuery ハンズオンチュートリアル

ブログサービスを題材に、Ray.MediaQuery 1.1.0 までの主要機能を13章で体験する入門。

- 前提: PHP 8.2+ / Composer / SQL の基礎 / DI の概念
- DB: SQLite (`:memory:`) — 追加 DB サーバーは不要

## このチュートリアルの読み方

各章は以下の流れで進む。

1. **ゴール** — その章で何ができるようになるか
2. **Step** — SQL → Interface → `run.php` 追記の順にコードを書く
3. **実行と期待出力** — 写経中の `run.php` を `php docs/tutorial/src/run.php` で動かして動作を確認
4. **解説** — フレームワーク内部で何が起きているか
5. **次章へ**

書き上がったコードは [`docs/tutorial/src/`](https://github.com/ray-di/Ray.MediaQuery/tree/1.x/docs/tutorial/src) 配下に「答え」として置いてある。詰まったら参照してよい。

> **完成版 `run.php` について**: [`docs/tutorial/src/run.php`](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/docs/tutorial/src/run.php) は全章を通しで実行する完成形の統合デモである。各章の「期待出力」は、読者がその章まで順にコードを追記・書き換えた途中状態を想定している。そのため、完成版をそのまま実行した出力は、章ごとの期待出力とは順序や表示内容が異なる。

また、このチュートリアルでは同じメソッド定義を章が進むにつれて意図的に書き換える。例えば `add()` は、第3章では `AffectedRows`、第6章では `void`、第10章以降では完成形の `InsertedRow` を返す。途中の形を体験しながら、最後に完成版へ収束する構成である。

このチュートリアルは **Ray.MediaQuery 1.1.0 以降** を前提にしている。1.1.0 で追加・修正された以下の機能もハンズオンに含めているため、1.0 系では後半の章がそのままでは動かない。

- `PostQueryInterface` による型付き結果構築 (DML 結果と SELECT 結果ラッパー)
- `AffectedRows` / `InsertedRow`
- `#[Pager]` と `#[DbQuery(factory: ...)]` を併用したページ内 row の factory hydration

全部を一度に進める必要はない。まず実装感を掴むなら第0章から第6章までで止めてもよい。Ray.MediaQuery 1.1 の追加機能を確認したい場合は、第9章から第12章と最後の結論を読むと、`AffectedRows` / `InsertedRow` / `PostQueryInterface` の位置付けが分かる。

## 戻り値型の早見表

Ray.MediaQuery では、SQL の種類だけでなく **メソッドの戻り値型** が結果の扱い方を決める重要な契約になる。

| 戻り値型 / docblock | 意味 |
|---------------------|------|
| `array` | 複数行を連想配列のリストとして返す |
| `?array` + `type: 'row'` | 1行を連想配列として返す。行がなければ `null` |
| `/** @return array<Article> */ array` | 複数行を `Article` オブジェクトのリストに hydrate して返す |
| `?Article` + `type: 'row'` | 1行を `Article` オブジェクトとして返す。行がなければ `null` |
| `void` | DML を実行し、結果は受け取らない |
| `AffectedRows` | INSERT / UPDATE / DELETE の影響行数を返す |
| `InsertedRow` | INSERT 後の auto-increment id と、注入・変換済みの bound 値を返す |
| `Pages<Article>` | ページングされた `Article` リストを返す |
| `PostQueryInterface` 実装 | 実行後の `PostQueryContext` から自作の結果オブジェクトを構築する |

SQL は読みやすさを優先した “Holywell-lite” の表記で揃える。

- SQL キーワードは大文字にする。
- テーブル名・カラム名は小文字の `snake_case` にする。
- `SELECT` の複数カラムは 1 行 1 カラムで書く。
- インデントは 4 spaces にする。
- alias は `AS` を明示する。
- チュートリアル内の SQL ファイルは末尾に `;` を付ける。特に multi-statement SQL では各 statement の `;` が必須。
- 短い `INSERT` / `DELETE` は写しやすさを優先して 1-2 行で保つ。

SQL プレースホルダは例外で、PHP の引数名に合わせて `:authorName` のような camelCase を使う。

## 完成形のディレクトリ構成

```
docs/tutorial/src/
├── run.php                  # 全章を順に実行するエントリーポイント
├── schema.sql               # テーブル定義
├── Blog/
│   ├── Article.php
│   ├── ArticleQueryInterface.php
│   ├── Comment.php
│   ├── CommentQueryInterface.php
│   ├── ArticleId.php                # ToScalarInterface 実装
│   ├── ArticleStats.php
│   ├── ArticleStatsFactory.php      # DI ファクトリ
│   ├── MarkdownExcerpter.php        # ファクトリへの注入対象
│   ├── ArticleSearchResult.php      # SELECT 用 PostQueryInterface
│   └── CreatedArticle.php           # DML + SELECT 用 PostQueryInterface
└── sql/
    ├── article_add.sql
    ├── article_create_and_get.sql
    ├── article_item.sql
    ├── article_list.sql
    ├── article_update.sql
    ├── article_delete.sql
    ├── article_paginated.sql
    ├── article_search.sql
    ├── article_stats.sql
    ├── article_stats_paginated.sql
    ├── comment_add.sql
    └── comment_list.sql
```

## 目次

| 章 | タイトル | 扱う機能 |
|----|---------|---------|
| [第0章](#第0章-はじめに--セットアップ) | はじめに / セットアップ | autoload・SQLite `:memory:` |
| [第1章](#第1章-最初のクエリ-一覧取得) | 最初のクエリ: 一覧取得 | `#[DbQuery]` / SELECT (row_list) |
| [第2章](#第2章-単一行の取得) | 単一行の取得 | `#[DbQuery(type: 'row')]` |
| [第3章](#第3章-insert-と-affectedrows) | INSERT と AffectedRows | INSERT / `AffectedRows` |
| [第4章](#第4章-エンティティへの自動マッピング) | エンティティへの自動マッピング | Constructor Promotion / readonly |
| [第5章](#第5章-snake_case--camelcase) | snake_case ↔ camelCase | `StringCase` 自動変換 |
| [第6章](#第6章-datetime-と-toscalar) | DateTime と ToScalar | `DateTimeInterface` / `ToScalarInterface` |
| [第7章](#第7章-ファクトリで派生値を作る) | ファクトリで派生値を作る | `factory:` (静的ファクトリ) |
| [第8章](#第8章-ファクトリへ依存注入) | ファクトリへ依存注入 | `factory:` (DI ファクトリ) |
| [第9章](#第9章-update--delete-と影響行数) | UPDATE / DELETE と影響行数 | `AffectedRows` (1.1) |
| [第10章](#第10章-insert-で-id-と確定値を得る) | INSERT で id と確定値を得る | `InsertedRow` (1.1) |
| [第11章](#第11章-ページネーション) | ページネーション | `#[Pager]` / `Pages<Article>` / `factory:` hydration (1.1) |
| [第12章](#第12章-自作-postqueryinterface) | 自作 PostQueryInterface | SELECT 対応 `PostQueryInterface::fromContext()` (1.1) |
| [第13章](#第13章-テスト戦略) | テスト戦略 | Fake バインディング |
| [結論](#結論-repository-pattern-との違い) | Repository Pattern との違い | Query-first / CQRS Read Model |

---

## 第0章: はじめに / セットアップ

### ゴール

- 作業ディレクトリの作成
- composer の autoload を準備
- SQLite メモリ DB に空のスキーマを流して動作確認できる状態にする

### Step 1. リポジトリを clone して composer install

```bash
php -m | grep '^pdo_sqlite$'
git clone https://github.com/ray-di/Ray.MediaQuery.git
cd Ray.MediaQuery
composer install --no-dev
```

`pdo_sqlite` が表示されれば OK。

### Step 2. ディレクトリ構造を作る

写経しながら進めるなら、以下の空ディレクトリを作っておく (このチュートリアルの完成形は `docs/tutorial/src/` に置いてあるので、自分用に別の場所で進めたい場合は `mywork/blog/` などに作ってよい)。

```bash
mkdir -p docs/tutorial/src/Blog docs/tutorial/src/sql
```

### Step 3. `composer.json` に autoload を追加

`autoload-dev.psr-4` に `Tutorial\Blog\\` を追加する。

```json
{
    "autoload-dev": {
        "psr-4": {
            "Ray\\MediaQuery\\": ["tests/", "tests/Fake"],
            "Tutorial\\Blog\\": "docs/tutorial/src/Blog/"
        }
    }
}
```

`composer dump-autoload` で反映する。

```bash
composer dump-autoload
```

> このチュートリアルの完成版 `run.php` は、写経中でもすぐ動かせるように `$loader->addPsr4()` でも `Tutorial\Blog\` を登録している。実プロジェクトや PHPUnit から使う場合は、ここで示したように `composer.json` に登録するのが基本。

### Step 4. スキーマ

`docs/tutorial/src/schema.sql`:

```sql
CREATE TABLE IF NOT EXISTS article (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    author_name TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    article_id INTEGER NOT NULL,
    body TEXT NOT NULL,
    posted_at TEXT NOT NULL
);
```

### 解説

- **`AUTOINCREMENT`**: 第10章で `InsertedRow::$id` を扱うため、`id` は最初から自動採番にしておく。
- **`published_at TEXT`**: SQLite には `DATETIME` 型がない。すべての日時は文字列として保存される。第6章で `DateTimeImmutable` を渡すと自動で `'Y-m-d H:i:s'` 文字列に変換される。

第1章から実際にコードを書き始める。

---

## 第1章: 最初のクエリ: 一覧取得

### ゴール

- インターフェースに `#[DbQuery('id')]` を付け、`id.sql` ファイルを置くだけで「実装ゼロでクエリが動く」感覚を体験する。
- 戻り値型 `array` で複数行を連想配列のリストとして取り出す。

### Step 1. SQL を書く

`docs/tutorial/src/sql/article_list.sql`:

```sql
SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
ORDER BY id;
```

### Step 2. インターフェースを書く

`docs/tutorial/src/Blog/ArticleQueryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleQueryInterface
{
    #[DbQuery('article_list')]
    public function list(): array;
}
```

### Step 3. `run.php` を作る

`docs/tutorial/src/run.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Aura\Sql\ExtendedPdoInterface;
use Composer\Autoload\ClassLoader;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;

/** @var ClassLoader $loader */
$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';
$loader->addPsr4('Tutorial\\Blog\\', __DIR__ . '/Blog');

$sqlDir = __DIR__ . '/sql';
$dsn = 'sqlite::memory:';

$injector = new Injector(new class ($sqlDir, $dsn) extends AbstractModule {
    public function __construct(
        private readonly string $sqlDir,
        private readonly string $dsn,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $queries = Queries::fromClasses([
            ArticleQueryInterface::class,
        ]);
        $this->install(new MediaQueryModule($queries, [new DbQueryConfig($this->sqlDir)]));
        $this->install(new AuraSqlModule($this->dsn));
    }
});

/** @var ExtendedPdoInterface $pdo */
$pdo = $injector->getInstance(ExtendedPdoInterface::class);
foreach (preg_split('/;\\s*/', trim((string) file_get_contents(__DIR__ . '/schema.sql'))) ?: [] as $stmt) {
    if ($stmt !== '') {
        $pdo->query($stmt);
    }
}

// 1件だけ仕込んで一覧を取る
$pdo->perform(
    'INSERT INTO article (title, body, author_name, status, created_at) VALUES (?, ?, ?, ?, ?)',
    ['Hello', 'first body', 'Alice', 'published', '2026-04-01 09:00:00'],
);

/** @var ArticleQueryInterface $repo */
$repo = $injector->getInstance(ArticleQueryInterface::class);

var_dump($repo->list());
```

### 実行

```bash
php docs/tutorial/src/run.php
```

### 期待出力

```
array(1) {
  [0]=>
  array(7) {
    ["id"]=>
    string(1) "1"
    ["title"]=>
    string(5) "Hello"
    ["body"]=>
    string(10) "first body"
    ["author_name"]=>
    string(5) "Alice"
    ["status"]=>
    string(9) "published"
    ["published_at"]=>
    NULL
    ["created_at"]=>
    string(19) "2026-04-01 09:00:00"
  }
}
```

### 解説

`ArticleQueryInterface` には実装クラスがない。にもかかわらず `$injector->getInstance(ArticleQueryInterface::class)` でインスタンスが取れる。これは Ray.Aop が `#[DbQuery]` 付きメソッドをインターセプトし、`article_list.sql` を読み込んで実行する「自動生成された実装」を返しているため。

- `#[DbQuery('article_list')]` の `'article_list'` は `sql/article_list.sql` のファイル名 (拡張子なし) と一致する。
- 戻り値型 `array` は「複数行の連想配列リスト」を意味する。型名で挙動が変わるのが Ray.MediaQuery のコア。
- カラム名は SQLite が返すままの snake_case (`author_name`, `published_at`)。第5章で camelCase 変換を扱う。

---

## 第2章: 単一行の取得

### ゴール

- 1行だけ返す SQL では `type: 'row'` を指定する
- 戻り値型 `array` のままで、連想配列1つを直接受け取る

### Step 1. SQL を書く

`sql/article_item.sql`:

```sql
SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
WHERE id = :id;
```

### Step 2. インターフェースに追記

`Blog/ArticleQueryInterface.php`:

```php
#[DbQuery('article_item', type: 'row')]
public function item(int $id): ?array;
```

### Step 3. `run.php` に追記

```php
$row = $repo->item(1);
var_dump($row);
```

### 期待出力

```
array(7) {
  ["id"]=>
  string(1) "1"
  ["title"]=>
  string(5) "Hello"
  ...
}
```

### 解説

- `type: 'row'` は単一行モード。`fetch()` 相当の動作になる。
- デフォルトは `type: 'row_list'` (= `fetchAll()` 相当)。
- 同じ SQL ファイルでも、戻り値型と `type` の組み合わせで結果の形が変わる。

---

## 第3章: INSERT と AffectedRows

### ゴール

- Ray.MediaQuery 1.1 の `AffectedRows` で、最初の書き込みクエリの影響行数を受け取る。
- DML も戻り値型で意図を宣言できることを確認する。

### Step 1. SQL を書く

`sql/article_add.sql`:

```sql
INSERT INTO article (title, body, author_name, status, published_at, created_at)
VALUES (:title, :body, :authorName, :status, :publishedAt, :createdAt);
```

### Step 2. インターフェースに追記

この章ではまず `AffectedRows` を返す。以降の章で同じ `add()` を一時的に `void` にし、最後に `InsertedRow` へ書き換える。完成形の [`ArticleQueryInterface.php`](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/docs/tutorial/src/Blog/ArticleQueryInterface.php) では、第10章まで進んだ後の `InsertedRow` 版になっている。

```php
use Ray\MediaQuery\Result\AffectedRows;

#[DbQuery('article_add')]
public function add(
    string $title,
    string $body,
    string $authorName,
    string $status,
    ?string $publishedAt,
    string $createdAt,
): AffectedRows;
```

### Step 3. `run.php` に追記

```php
$affected = $repo->add(
    title: 'Second',
    body: 'about SQL and Objects',
    authorName: 'Bob',
    status: 'published',
    publishedAt: '2026-04-02 10:00:00',
    createdAt: '2026-04-02 10:00:00',
);
printf("insert affected=%d\n", $affected->count);
var_dump($repo->list());
```

### 期待出力

```
insert affected=1
array(2) {
  [0] => array(7) { ... "Hello" ... }
  [1] => array(7) { ... "Second" ... }
}
```

### 解説

- メソッドの引数名 (`$title`, `$authorName` など) と SQL のプレースホルダ (`:title`, `:authorName`) が同名であれば自動でバインドされる。**順番は問われない**。
- 戻り値 `AffectedRows` は「DML の影響行数を見る」という宣言。INSERT / UPDATE / DELETE のいずれにも使える。
- 第10章では同じ INSERT を `InsertedRow` に変えて、自動採番 id と変換後の bound 値まで取得する。

---

## 第4章: エンティティへの自動マッピング

### ゴール

- 連想配列ではなく `Article` オブジェクトとして結果を受け取る。
- Constructor Property Promotion + `readonly` で immutable Entity を書く。

### Step 1. Entity を書く

`Blog/Article.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

final class Article
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $body,
        public readonly string $authorName,    // ← snake_case の author_name にマッピング (次章で詳しく)
        public readonly string $status,
        public readonly ?string $publishedAt,
        public readonly string $createdAt,
    ) {
    }
}
```

### Step 2. インターフェースの戻り値型を変える

```php
/** @return array<Article> */
#[DbQuery('article_list')]
public function list(): array;

#[DbQuery('article_item', type: 'row')]
public function item(int $id): ?Article;
```

### Step 3. `run.php` で使う

```php
$articles = $repo->list();
foreach ($articles as $a) {
    printf("[%d] %s by %s\n", $a->id, $a->title, $a->authorName);
}

$first = $repo->item(1);
echo $first?->title, "\n";
```

### 期待出力

```
[1] Hello by Alice
[2] Second by Bob
Hello
```

### 解説

- 戻り値型 `?Article` (単一) や docblock `@return array<Article>` (複数) を見て、フレームワークが `PDO::FETCH_CLASS` を使ってオブジェクトに hydrate する。
- Constructor Promotion のおかげで getter / setter は不要。`readonly` で意図せぬ変更を防ぐ。
- PHP 8.4 以降なら `final readonly class Article { ... }` と書けばさらに簡潔。

---

## 第5章: snake_case ↔ camelCase

### ゴール

- DB カラム `author_name` が PHP プロパティ `$authorName` に自動マッピングされることを確認する。

### この章でやること

実は第4章の時点で既に動いている。改めて確認するだけ。

```php
echo $first->authorName;  // "Alice"
echo $first->publishedAt; // null or '2026-04-01 09:00:00'
```

### 解説

- Ray.MediaQuery 内部の `StringCase::camel()` がカラム名を camelCase に変換し、`PDO::FETCH_CLASS` のプロパティ代入に渡している。
- 実装は `src/StringCase.php`。
- DB の命名規則 (snake_case が一般的) と PHP の命名規則 (camelCase が一般的) を**両方とも自然に保てる**のがこの機能の価値。
- `factory:` 属性を使うとき (第7章) は引数名がそのままバインドされる (camelCase 引数名 ← snake_case カラム名 の対応は SQL のカラム順による)。

---

## 第6章: DateTime と ToScalar

### ゴール

- 引数に `DateTimeImmutable` を直接渡し、自動で SQL 文字列に変換されることを見る。
- 値オブジェクト (`ArticleId`) を `ToScalarInterface` 経由でスカラーに変換する。
- `null` 既定値による自動注入の仕組みを知る。

### Step 1. ArticleId 値オブジェクトを書く

`Blog/ArticleId.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Ray\MediaQuery\ToScalarInterface;

final class ArticleId implements ToScalarInterface
{
    public function __construct(
        public readonly int $value,
    ) {
    }

    public function toScalar(): int
    {
        return $this->value;
    }
}
```

### Step 2. インターフェースを進化させる

この章では `DateTimeInterface` の自動変換に集中するため、`add()` の戻り値をいったん `void` にする。第10章で `InsertedRow` に戻し、同じ INSERT から id と変換後の値を取り出す。

```php
use DateTimeInterface;

#[DbQuery('article_item', type: 'row')]
public function item(ArticleId $id): ?Article;

#[DbQuery('article_add')]
public function add(
    string $title,
    string $body,
    string $authorName,
    string $status = 'draft',
    ?DateTimeInterface $publishedAt = null,
    ?DateTimeInterface $createdAt = null,
): void;
```

### Step 3. `run.php` で使う

```php
use DateTimeImmutable;

$repo->add(
    title: 'Third',
    body: 'about DateTime',
    authorName: 'Carol',
    status: 'published',
    publishedAt: new DateTimeImmutable('2026-04-03 11:00:00'),
    createdAt: new DateTimeImmutable('2026-04-03 11:00:00'),
);

$article = $repo->item(new ArticleId(3));
var_dump($article->publishedAt);
```

### 期待出力

```
string(19) "2026-04-03 11:00:00"
```

### 解説

- **DateTime → 文字列**: `ParamConverter` が `DateTimeInterface` を検出し、`'Y-m-d H:i:s'` 形式の文字列に変換してから PDO に渡す。
- **ToScalarInterface**: `ArticleId::toScalar()` の返り値 (int) がそのまま `:id` にバインドされる。「コードの中では型安全な値オブジェクトとして扱い、SQL 境界で自動的にスカラーに変換」というパターン。
- **`null` 既定値**: `?DateTimeInterface = null` のように既定値が `null` の場合、引数を省略すると Ray.Di から `DateTimeInterface` 実装が注入される (`ParamInjector`)。このチュートリアルでは第10章で `InsertedRow::$values` を使って、注入後・変換後の値を観測する。

> SQLite には `DATETIME` 型がないので、再取得すると string になる。MySQL や PostgreSQL では DB 側の型に応じた挙動になる。

---

## 第7章: ファクトリで派生値を作る

### ゴール

- DB から取った値だけでは作れない「派生値 (excerpt, commentCount, published フラグなど)」を持つオブジェクトを返す。
- `factory:` 属性で静的ファクトリメソッドを呼ばせる。

### Step 1. ArticleStats Entity

`Blog/ArticleStats.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

final class ArticleStats
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $excerpt,
        public readonly int $commentCount,
        public readonly bool $published,
    ) {
    }
}
```

### Step 2. SQL と Comment 関連を準備

`sql/article_stats.sql`:

```sql
SELECT
    a.id,
    a.title,
    a.body,
    (
        SELECT COUNT(*)
        FROM comment AS c
        WHERE c.article_id = a.id
    ) AS comment_count,
    a.status
FROM article AS a
WHERE a.id = :id;
```

> **重要**: ファクトリメソッドの引数は **SELECT のカラム順** で渡される (`PDO::FETCH_FUNC` の挙動)。**引数名ではなく順序が一致**している必要がある。

### Step 3. ファクトリ (静的版)

まずは最も単純な静的ファクトリを示す。次章で DI 版に進化させる。

```php
namespace Tutorial\Blog;

final class ArticleStatsFactory
{
    public static function factory(
        int $id,
        string $title,
        string $body,
        int $commentCount,
        string $status,
    ): ArticleStats {
        $excerpt = mb_strlen($body) <= 60 ? $body : mb_substr($body, 0, 60) . '…';

        return new ArticleStats(
            id: $id,
            title: $title,
            excerpt: $excerpt,
            commentCount: $commentCount,
            published: $status === 'published',
        );
    }
}
```

### Step 4. インターフェースに追記

```php
#[DbQuery('article_stats', type: 'row', factory: ArticleStatsFactory::class)]
public function stats(ArticleId $id): ArticleStats;
```

### Step 5. `run.php` で使う

(第7章までは Comment が無いので commentCount=0 になる。次章で comment を入れる。)

```php
$stats = $repo->stats(new ArticleId(1));
var_dump($stats);
```

### 期待出力 (この時点)

```
object(Tutorial\Blog\ArticleStats)#... {
  ["id"]=> int(1)
  ["title"]=> string(5) "Hello"
  ["excerpt"]=> string(...) "..."
  ["commentCount"]=> int(0)
  ["published"]=> bool(true)
}
```

### 解説

- **静的ファクトリ vs DI ファクトリ**: メソッドが `static` なら静的ファクトリ (`FetchStaticFactory`)、インスタンスメソッドなら DI ファクトリ (`FetchInjectionFactory`) が選ばれる。
- **派生値の表現力**: 「DB に存在しない値 (excerpt, published フラグ)」をオブジェクトの主な責務にできる。Entity は「ただの行データ」にとどまらない。

---

## 第8章: ファクトリへ依存注入

### ゴール

- ファクトリ自体に依存を注入し、サービスを使いながら派生値を計算する (BDR パターンの核心)。

### Step 1. 注入対象のサービス

`Blog/MarkdownExcerpter.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

final class MarkdownExcerpter
{
    public function excerpt(string $body, int $length): string
    {
        $plain = trim(strip_tags($body));
        if (mb_strlen($plain) <= $length) {
            return $plain;
        }

        return mb_substr($plain, 0, $length) . '…';
    }
}
```

### Step 2. ファクトリを DI 版に書き換える

```php
namespace Tutorial\Blog;

final class ArticleStatsFactory
{
    public function __construct(
        private readonly MarkdownExcerpter $excerpter,
    ) {
    }

    public function factory(
        int $id,
        string $title,
        string $body,
        int $commentCount,
        string $status,
    ): ArticleStats {
        return new ArticleStats(
            id: $id,
            title: $title,
            excerpt: $this->excerpter->excerpt($body, 60),
            commentCount: $commentCount,
            published: $status === 'published',
        );
    }
}
```

### Step 3. Module で `MarkdownExcerpter` を bind

`run.php` の Module の `configure()` に追加:

```php
$this->bind(MarkdownExcerpter::class);
```

### Step 4. `run.php` でコメントを足す

Comment 用の SQL と interface も用意する。

`sql/comment_add.sql`:

```sql
INSERT INTO comment (article_id, body, posted_at)
VALUES (:articleId, :body, :postedAt);
```

`Blog/CommentQueryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use DateTimeInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\InsertedRow;

interface CommentQueryInterface
{
    #[DbQuery('comment_add')]
    public function add(
        int $articleId,
        string $body,
        ?DateTimeInterface $postedAt = null,
    ): InsertedRow;
}
```

`run.php` の `Queries::fromClasses()` に `CommentQueryInterface::class` を追加し、`$commentRepo` を取得する。

```php
$queries = Queries::fromClasses([
    ArticleQueryInterface::class,
    CommentQueryInterface::class,
]);

/** @var CommentQueryInterface $commentRepo */
$commentRepo = $injector->getInstance(CommentQueryInterface::class);
```

コメントを追加してから `stats()` を呼ぶ。

```php
$commentRepo->add(1, 'Great post!', new DateTimeImmutable('2026-04-01 12:00:00'));
$commentRepo->add(1, 'Thanks!',     new DateTimeImmutable('2026-04-01 13:00:00'));

$stats = $repo->stats(new ArticleId(1));
printf("commentCount=%d, excerpt='%s'\n", $stats->commentCount, $stats->excerpt);
```

### 期待出力

```
commentCount=2, excerpt='This is the first post about interface-driven SQL.'
```

### 解説

- ファクトリは Ray.Di 経由でインスタンス化されるので、コンストラクタで自由にサービスを注入できる。
- これが Ray.MediaQuery を「単なるクエリマッパー」と区別する点 — **SQL の結果に対してドメイン処理を効率良く適用できる**。
- Business Domain Repository (BDR) パターンは [BDR_PATTERN-ja.md](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN-ja.md) で詳述。

---

## 第9章: UPDATE / DELETE と影響行数

### ゴール

- 第3章で INSERT に使った `AffectedRows` を、UPDATE / DELETE にも同じ形で使う。

### Step 1. SQL を書く

`sql/article_update.sql`:

```sql
UPDATE article
SET
    title = :title,
    body = :body
WHERE id = :id;
```

`sql/article_delete.sql`:

```sql
DELETE FROM article
WHERE id = :id;
```

### Step 2. インターフェースに追記

```php
use Ray\MediaQuery\Result\AffectedRows;

#[DbQuery('article_update')]
public function update(ArticleId $id, string $title, string $body): AffectedRows;

#[DbQuery('article_delete')]
public function delete(ArticleId $id): AffectedRows;
```

### Step 3. `run.php` で使う

```php
$updated = $repo->update(new ArticleId(1), 'Hello (edited)', 'updated body');
printf("updated count=%d, isAffected=%s\n", $updated->count, $updated->isAffected() ? 'yes' : 'no');

$deleted = $repo->delete(new ArticleId(2));
printf("deleted count=%d\n", $deleted->count);
```

### 期待出力

```
updated count=1, isAffected=yes
deleted count=1
```

### 解説

- `AffectedRows` は `final readonly class` で、`int $count` プロパティと `isAffected(): bool` メソッドだけを持つ。
- 戻り値型に `AffectedRows` と書くだけで、フレームワークが `$statement->rowCount()` を呼んで構築してくれる。
- 第3章の INSERT と同じく、SQL の種類をフレームワークに推測させるのではなく、戻り値型で「何を知りたいか」を宣言する。

---

## 第10章: INSERT で id と確定値を得る

### ゴール

- Ray.MediaQuery 1.1 で追加された `InsertedRow` 戻り値で、自動採番された `id` と「フレームワークが解決して DB に渡した値」を取り出す。
- 第3章の `AffectedRows` では足りない場面で、INSERT 専用の結果型を選ぶ判断基準を知る。

### Step 1. インターフェースを書き換える

```php
use Ray\MediaQuery\Result\InsertedRow;

#[DbQuery('article_add')]
public function add(
    string $title,
    string $body,
    string $authorName,
    string $status = 'draft',
    ?DateTimeInterface $publishedAt = null,
    ?DateTimeInterface $createdAt = null,
): InsertedRow;
```

### Step 2. `run.php` で使う

```php
$inserted = $repo->add(
    title: 'Hello',
    body: 'first body',
    authorName: 'Alice',
    status: 'published',
    publishedAt: new DateTimeImmutable('2026-04-01 09:00:00'),
    createdAt:   new DateTimeImmutable('2026-04-01 09:00:00'),
);

printf("id=%s\n", $inserted->id);
var_dump($inserted->values);
```

このメソッド定義では、`publishedAt` や `createdAt` を省略すると、`ParamInjector` が `DateTimeInterface` を注入し、`ParamConverter` が SQL 用の文字列に変換する。

```php
$draft = $repo->add(
    title: 'Draft',
    body: 'createdAt is injected',
    authorName: 'Dana',
);

var_dump($draft->values['createdAt']);
```

### 期待出力

```
id=1
array(6) {
  ["title"]=> string(5) "Hello"
  ["body"]=> string(10) "first body"
  ["authorName"]=> string(5) "Alice"
  ["status"]=> string(9) "published"
  ["publishedAt"]=> string(19) "2026-04-01 09:00:00"
  ["createdAt"]=> string(19) "2026-04-01 09:00:00"
}
string(19) "2026-04-25 12:34:56" // 実行時刻の例
```

### 解説

- **`$inserted->id`**: `pdo->lastInsertId()` の結果。`AUTOINCREMENT` 列がある場合は新しい id が文字列で返る。
- **`$inserted->values`**: ParamConverter / ParamInjector が解決した「実際に DB に渡した値」。`DateTimeImmutable` は文字列に、`ToScalarInterface` はスカラーに、それぞれ変換済み。**呼び出し側からはこれ以外の方法では観測できない値**。
- **使い分け**:
  - 何件入ったかだけ知りたい → `AffectedRows`
  - id や解決後の値を取り戻したい → `InsertedRow`
  - 何も要らない → `void`

---

## 第11章: ページネーション

### ゴール

- `#[Pager]` で大量データを Pages として扱う。
- `Pages<Article>` で Article エンティティへの hydration を保ったままページングする。
- Ray.MediaQuery 1.1 で修正された `#[Pager]` + `factory:` の hydration を確認する。

### Step 1. SQL

`sql/article_paginated.sql` (中身は `article_list.sql` と同じでよい):

```sql
SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
ORDER BY id;
```

### Step 2. インターフェースに追記

```php
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

/** @return Pages<Article> */
#[DbQuery('article_paginated'), Pager(perPage: 10)]
public function paginated(): Pages;
```

### Step 3. `run.php` でデータを増やす

```php
for ($i = 3; $i <= 32; $i++) {
    $repo->add(
        title: "Post #{$i}",
        body: "Body for post {$i}.",
        authorName: 'Carol',
        status: 'published',
        publishedAt: new DateTimeImmutable('2026-04-03 00:00:00'),
        createdAt:   new DateTimeImmutable('2026-04-03 00:00:00'),
    );
}

$pages = $repo->paginated();
$page1 = $pages[1];

printf("total items=%d\n", count($pages));
printf("page 1 has %d items, hasNext=%s\n", count($page1->data), $page1->hasNext ? 'yes' : 'no');
echo $page1->data[0]->title, "\n";
```

### 期待出力

```
total items=31
page 1 has 10 items, hasNext=yes
Hello (edited)
```

### Step 4. Ray.MediaQuery 1.1: Pager と factory を組み合わせる

1.1.0 では、`#[Pager]` 付きのクエリでも `#[DbQuery(factory: ...)]` が尊重される。ページ内の `$page->data` も factory 経由のオブジェクトになることを確認する。

`sql/article_stats_paginated.sql`:

```sql
SELECT
    a.id,
    a.title,
    a.body,
    (
        SELECT COUNT(*)
        FROM comment AS c
        WHERE c.article_id = a.id
    ) AS comment_count,
    a.status
FROM article AS a
ORDER BY a.id;
```

`Blog/ArticleQueryInterface.php` に追記:

```php
/** @return Pages<ArticleStats> */
#[DbQuery('article_stats_paginated', factory: ArticleStatsFactory::class), Pager(perPage: 10)]
public function statsPaginated(): Pages;
```

`run.php` で確認する:

```php
$statsPages = $repo->statsPaginated();
$statsPage1 = $statsPages[1];
$firstStats = $statsPage1->data[0];

printf(
    "first stats row=%s commentCount=%d excerpt='%s'\n",
    $firstStats::class,
    $firstStats->commentCount,
    $firstStats->excerpt,
);
```

### 期待出力

```
first stats row=Tutorial\Blog\ArticleStats commentCount=2 excerpt='Updated body.'
```

### 解説

- `count($pages)` は **総アイテム数** (= COUNT クエリの結果)。総ページ数ではないので注意。
- `$pages[1]` でページ1にアクセス → SELECT に LIMIT/OFFSET が付いて実行される (lazy)。
- `$page->data` は Article のリスト (`@return Pages<Article>` のおかげで hydration が効く)。
- `#[DbQuery(factory: ArticleStatsFactory::class)]` と `#[Pager]` を併用した場合、1.1 以降は `$page->data` の各行も `ArticleStatsFactory` で作られる。
- `$page->hasNext` / `$page->hasPrevious` / `$page->current` で UI を組める。`(string) $page` で HTML レンダリングも可能。
- 動的ページサイズ (`perPage: 'perPage'`) など発展形は README を参照。

---

## 第12章: 自作 PostQueryInterface

### ゴール

- `AffectedRows` / `InsertedRow` のような結果型を自分で作る。
- Ray.MediaQuery 1.1 で SELECT にも拡張された `PostQueryInterface` を使う。
- SELECT の結果を「マッチ件数 + 実行された SQL 文字列」付きで返す検索機能を作る。

### Step 1. SQL

`sql/article_search.sql`:

```sql
SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
WHERE
    title LIKE :keyword
    OR body LIKE :keyword
ORDER BY id;
```

### Step 2. 結果クラスを書く

`Blog/ArticleSearchResult.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class ArticleSearchResult implements PostQueryInterface
{
    /** @param array<Article> $rows */
    public function __construct(
        public readonly array $rows,
        public readonly int $matched,
        public readonly string $sql,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        return new static(
            rows: $context->rows,
            matched: count($context->rows),
            sql: $context->statement->queryString,
        );
    }
}
```

### Step 3. インターフェースに追記

```php
/** @return ArticleSearchResult<Article> */
#[DbQuery('article_search')]
public function search(string $keyword): ArticleSearchResult;
```

`@return ArticleSearchResult<Article>` の docblock がエンティティ hydration のヒントになり、`$context->rows` には Article のリストが渡る。これは PHP の実行時型を変えるための構文ではなく、Ray.MediaQuery が `PostQueryInterface` 実装の内側に入れる row の型を読み取るためのメタデータである。

### Step 4. `run.php` で使う

```php
$result = $repo->search('%Post%');
printf("matched=%d\n", $result->matched);
echo "SQL: ", $result->sql, "\n";
echo "First hit: ", $result->rows[0]->title, "\n";
```

### 期待出力

```
matched=30
SQL: SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
WHERE
    title LIKE :keyword
    OR body LIKE :keyword
ORDER BY id;
First hit: Post #3
```

### 解説

- `PostQueryInterface` の唯一の契約は `static fromContext(PostQueryContext): static`。
- `PostQueryContext` には次の情報が入る:
  - `$context->statement` (`PDOStatement`) — `rowCount()`, `queryString` など
  - `$context->pdo` (`ExtendedPdoInterface`) — `lastInsertId()` など
  - `$context->values` — ParamConverter / ParamInjector が解決した bound 値
  - `$context->rows` — SELECT パスでは hydrated 結果、DML パスでは `[]`
- **DML 用途** (`AffectedRows` のように `rowCount()` を使う) と **SELECT 用途** (`Articles` のように `rows` を使う) の両方に対応できる、極めて汎用的な拡張ポイント。
- 実装例は `src/Result/AffectedRows.php`, `src/Result/InsertedRow.php`, `tests/Fake/Result/Articles.php`, `tests/Fake/Result/RowCountWithQuery.php`。

---

## 第13章: テスト戦略

### ゴール

- 「インターフェースが契約」というアーキテクチャを利用して、ビジネスロジックをテストする。

### 考え方

`ArticleQueryInterface` は契約。プロダクションでは Ray.MediaQuery が SQLite/MySQL を叩く実装を自動生成するが、テストでは「Fake 実装」を bind すれば DB なしでロジックを検証できる。

### Step 1. Fake 実装を書く

```php
namespace Tutorial\Blog\Test;

use Tutorial\Blog\Article;
use Tutorial\Blog\ArticleId;
use Tutorial\Blog\ArticleQueryInterface;
use Tutorial\Blog\ArticleSearchResult;
use Tutorial\Blog\ArticleStats;
use Tutorial\Blog\CreatedArticle;
use Ray\MediaQuery\Pages;
use Ray\MediaQuery\Result\AffectedRows;
use Ray\MediaQuery\Result\InsertedRow;

final class FakeArticleQuery implements ArticleQueryInterface
{
    /** @var array<int, Article> */
    private array $store = [];

    public function list(): array { return array_values($this->store); }
    public function item(ArticleId $id): ?Article { return $this->store[$id->value] ?? null; }

    public function add(string $title, string $body, string $authorName, string $status = 'draft', ?\DateTimeInterface $publishedAt = null, ?\DateTimeInterface $createdAt = null): InsertedRow
    {
        $id = count($this->store) + 1;
        $this->store[$id] = new Article($id, $title, $body, $authorName, $status, $publishedAt?->format('Y-m-d H:i:s'), $createdAt?->format('Y-m-d H:i:s') ?? '');

        return new InsertedRow(
            values: compact('title', 'body', 'authorName', 'status'),
            id: (string) $id,
        );
    }

    public function update(ArticleId $id, string $title, string $body): AffectedRows { /* ... */ return new AffectedRows(1); }
    public function delete(ArticleId $id): AffectedRows { unset($this->store[$id->value]); return new AffectedRows(1); }
    public function paginated(): Pages { throw new \LogicException('not used in this test'); }
    public function statsPaginated(): Pages { throw new \LogicException('not used in this test'); }
    public function stats(ArticleId $id): ArticleStats { throw new \LogicException('not used'); }
    public function search(string $keyword): ArticleSearchResult { throw new \LogicException('not used'); }
    public function createAndGet(string $title, string $body, string $authorName, string $status = 'draft', ?\DateTimeInterface $createdAt = null): CreatedArticle
    {
        $inserted = $this->add($title, $body, $authorName, $status, null, $createdAt);

        return new CreatedArticle($this->store[(int) $inserted->id]);
    }
}
```

### Step 2. Module で差し替え

```php
$injector = new Injector(new class extends AbstractModule {
    protected function configure(): void
    {
        $this->bind(ArticleQueryInterface::class)->to(FakeArticleQuery::class)->in(\Ray\Di\Scope::SINGLETON);
    }
});

/** @var ArticleQueryInterface $repo */
$repo = $injector->getInstance(ArticleQueryInterface::class);
$repo->add('T', 'B', 'A');
assert($repo->item(new ArticleId(1))->title === 'T');
```

### 解説

- DB を使わずにロジックの単体テストができる。
- `tests/Fake/Queries/` には Ray.MediaQuery 自身のテストで使われている Fake interface 群が大量にある。「実プロジェクトでは PHPUnit でこう書く」の参考に良い。
- PHPUnit を使う場合は `composer require --dev phpunit/phpunit` 後、`PHPUnit\Framework\TestCase` を継承して同じ Module 差し替えパターンを使う。

---

## 結論: Repository Pattern との違い

ここまでのチュートリアルでは、Repository 実装クラスを書かずに、interface + attribute + SQL + return type でクエリを表現してきた。

これは Repository Pattern の単なる省コード化ではない。Repository が「永続化されたオブジェクト集合」を抽象化するのに対して、Ray.MediaQuery は「実行可能な Query 契約」を抽象化する。

| 観点 | Repository Pattern | Ray.MediaQuery |
|------|--------------------|----------------|
| 中心 | Entity / Aggregate | Query / UseCase |
| 主な用途 | Write Model, Aggregate の保存と復元 | Read Model, Projection, CQRS の Query 側 |
| 実装 | Repository class に手書き | Interface + Attribute + SQL |
| 結果加工 | Repository 実装内の手続き | `factory:` / `PostQueryInterface` |
| SQL | 実装の中に埋もれやすい | SQL ファイルとして明示される |
| 差し替え | Repository interface を Fake / Mock に差し替える | Query interface を Fake / Mock に差し替える |

Repository は不要になるわけではない。Aggregate を復元し、変更し、保存する Write 側では今でも有効な抽象である。

一方、Read 側では必要な形の Projection を UseCase ごとに取得したいことが多い。そこに Entity 中心の Repository を広げすぎると、dashboard、search、admin、analytics などの入口が一つの Repository に集まりやすい。いわば「一つの部屋に複数のドアがある」状態になる。

Ray.MediaQuery は、その Read 側を Query-first に分割する。`UserRepository` にメソッドを増やすのではなく、`UserDashboardQuery`、`ArticleSearchQuery`、`MonthlyStatsQuery` のように interaction そのものを契約にする。

### Multi-statement DML + SELECT

Ray.MediaQuery 1.1 の `PostQueryInterface` は、この Query-first の考え方をもう一段進める。SQL ファイルの最後の statement が SELECT なら、`PostQueryContext::$rows` にはその SELECT の hydrated 結果が入る。

例えば「記事を作成し、その作成済み行を返す」という use case は、Repository 実装では INSERT、last insert id の取得、SELECT、hydrate を手書きしがちである。Ray.MediaQuery では、この一連の interaction を SQL と戻り値型で宣言できる。

`sql/article_create_and_get.sql`:

```sql
INSERT INTO article (title, body, author_name, status, created_at)
VALUES (:title, :body, :authorName, :status, :createdAt);

SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
WHERE id = last_insert_rowid();
```

> `last_insert_rowid()` は SQLite の関数。MySQL なら `LAST_INSERT_ID()`、PostgreSQL や SQLite 3.35+ なら `INSERT ... RETURNING` を使う設計もできる。Ray.MediaQuery の複数 statement 分解では `;` が区切りなので、最後の SELECT にも `;` を付ける。

`Blog/CreatedArticle.php`:

```php
<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class CreatedArticle implements PostQueryInterface
{
    public function __construct(
        public readonly Article $article,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $article = $context->rows[0] ?? null;
        assert($article instanceof Article);

        return new static($article);
    }
}
```

`Blog/ArticleQueryInterface.php`:

```php
/** @return CreatedArticle<Article> */
#[DbQuery('article_create_and_get')]
public function createAndGet(
    string $title,
    string $body,
    string $authorName,
    string $status = 'draft',
    ?DateTimeInterface $createdAt = null,
): CreatedArticle;
```

`run.php`:

```php
$created = $repo->createAndGet(
    title: 'Created and fetched',
    body: 'A multi-statement query can return the row created by its first statement.',
    authorName: 'Eve',
);

printf(
    "created article id=%d title='%s' status=%s\n",
    $created->article->id,
    $created->article->title,
    $created->article->status,
);
```

### 期待出力

```
created article id=33 title='Created and fetched' status=draft
```

この例で重要なのは、`createAndGet()` が「ArticleRepository の便利メソッド」ではなく、「記事を作成して、その作成結果を型付きで返す Query 契約」になっている点である。

Write 側の Aggregate 永続化には Repository。Read 側や Projection 取得、DML 後の型付き結果取得には Query-first。Ray.MediaQuery は、この後者を interface と SQL で明示するための仕組みである。

---

## 完走おめでとう

ここまで読み終えると、Ray.MediaQuery の主要機能を一通り体験したことになる。

### 次に読むもの

- [BDR Pattern Guide 日本語版](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN-ja.md) — ファクトリパターンとドメインオブジェクトの設計
- [Feature Reference](https://ray-di.github.io/Ray.MediaQuery/reference/) — 機能リファレンス (`#[Input]` Object Flattening, `SqlQueryInterface` 直接実行などの応用)
- [llms-full.txt](../llms-full.txt) — AI エージェント向けの圧縮リファレンス
- [`tests/Fake/`](https://github.com/ray-di/Ray.MediaQuery/tree/1.x/tests/Fake) — 実際のテストコード

### コミュニティ

- [Issues](https://github.com/ray-di/Ray.MediaQuery/issues)
- [BEAR.Sunday](https://bearsunday.github.io/) — Ray.MediaQuery を内蔵するアプリケーションフレームワーク
