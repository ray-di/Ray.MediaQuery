---
layout: default
title: Ray.MediaQuery ハンズオンチュートリアル
description: ブログサービスを題材に、Ray.MediaQuery 1.1.0 までの主要機能を第0章から第13章＋補章で体験する入門
lang: ja
permalink: /tutorial/ja/
---

# Ray.MediaQuery ハンズオンチュートリアル

[English version](https://ray-di.github.io/Ray.MediaQuery/tutorial/)

ブログサービスを題材に、Ray.MediaQuery 1.1.0 までの主要機能を第0章から第13章＋補章で体験する入門。

- 前提: PHP 8.2+ / Composer / SQL の基礎 / DI の概念
- DB: SQLite (`:memory:`) — 追加 DB サーバーは不要

## このチュートリアルの読み方

各章は以下の流れで進む。

1. **ゴール** — その章で何ができるようになるか
2. **Step** — SQL → Interface → `run.php` 追記の順にコードを書く
3. **実行と期待出力** — 写経中の `run.php` を `php mywork/run.php` で動かして動作を確認
4. **解説** — フレームワーク内部で何が起きているか
5. **次章へ**

書き上がったコードは [`docs/tutorial/src/`](https://github.com/ray-di/Ray.MediaQuery/tree/1.x/docs/tutorial/src) 配下に「答え」として置いてある。詰まったら参照してよい。

> **完成版 `run.php` について**: [`docs/tutorial/src/run.php`](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/docs/tutorial/src/run.php) は全章を通しで実行する完成形の統合デモである。各章の「期待出力」には、その意味が分かりやすい参照フレームを選んで `(単独実行)` または `(統合 run.php)` のラベルを付けてある。
>
> - `(単独実行)` … その章のコードだけを小さな `run.php` で動かしたときの出力。前半の機能紹介ではこちらを使う。
> - `(統合 run.php)` … 完成版 `run.php` を頭から通しで実行し、前の章のデータ投入・UPDATE / DELETE まで積み上がった状態での出力。`id` や件数が前章までの累積に依存する後半ではこちらを使う。
>
> 自分で写経した `run.php` は、どの章をどの順で積み上げたかによって `id` や件数が変わる。数値そのものではなく「型と構造が期待どおりか」を確認してほしい。なお `run.php` が実際に実行するのは第1〜12章と補章までで、第13章（テスト戦略）は実行コードを持たない解説章である。

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

下図は `docs/tutorial/src/` に置かれた**完成版の「答え」**（名前空間 `Tutorial\Blog\`）である。自分で写経するコードは、これとは別に `mywork/`（名前空間 `MyBlog\`）に置く（第0章参照）。答えとは名前空間が違うので、同じリポジトリ内で並行しても衝突しない。

```text
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
│   ├── CreatedArticle.php           # DML + SELECT 用 PostQueryInterface
│   └── Exception/
│       └── UnexpectedRowException.php  # 結果クラスが投げるドメイン例外
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
| [第5章](#第5章-constructor-hydration-と-select-カラム順) | Constructor hydration と SELECT カラム順 | `FetchNewInstance` / hydration 経路 |
| [第6章](#第6章-datetime-と-toscalar) | DateTime と ToScalar | `DateTimeInterface` / `ToScalarInterface` |
| [第7章](#第7章-ファクトリで派生値を作る) | ファクトリで派生値を作る | `factory:` (静的ファクトリ) |
| [第8章](#第8章-ファクトリへ依存注入) | ファクトリへ依存注入 | `factory:` (DI ファクトリ) |
| [第9章](#第9章-update--delete-と影響行数) | UPDATE / DELETE と影響行数 | `AffectedRows` (1.1) |
| [第10章](#第10章-insert-で-id-と確定値を得る) | INSERT で id と確定値を得る | `InsertedRow` (1.1) |
| [第11章](#第11章-ページネーション) | ページネーション | `#[Pager]` / `Pages<Article>` / `factory:` hydration (1.1) |
| [第12章](#第12章-自作-postqueryinterface) | 自作 PostQueryInterface | SELECT 対応 `PostQueryInterface::fromContext()` (1.1) |
| [第13章](#第13章-テスト戦略) | テスト戦略 | Fake バインディング（run.php では非実行の解説章） |
| [補章](#補章-multi-statement-dml--select) | Multi-statement DML + SELECT | `PostQueryInterface` で INSERT + SELECT を1メソッド (1.1) |
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
composer install
```

`pdo_sqlite` が表示されれば OK。

### Step 2. ディレクトリ構造を作る

自分のコードは、答え (`docs/tutorial/src/`、名前空間 `Tutorial\Blog\`) とは**別のツリー** `mywork/` に、**別の名前空間 `MyBlog\`** で書く。こうすると、リポジトリに同梱された答えと同じ場所・同じ名前空間で衝突することがなく、「ライブラリを composer で入れて自分のコードを書く」実プロジェクトと同じ形になる。

リポジトリのルートで以下を作る。

```bash
mkdir -p mywork/blog mywork/sql
```

以降、本文で `Blog/Xxx.php` と書いてあるものは `mywork/blog/Xxx.php`、`sql/xxx.sql` は `mywork/sql/xxx.sql`、`run.php` は `mywork/run.php` を指す。

### Step 3. `composer.json` に autoload を追加

自分の `MyBlog\` 名前空間を `mywork/blog/` に対応づける。`run.php` を動かすだけなら次章のブートストラップ (`$loader->addPsr4()`) で足りるので必須ではないが、IDE 補完や PHPUnit から自分のクラスを使うなら登録しておく。

```json
{
    "autoload-dev": {
        "psr-4": {
            "MyBlog\\": "mywork/blog/"
        }
    }
}
```

`composer dump-autoload` で反映する。

```bash
composer dump-autoload
```

> リポジトリの実際の `composer.json` には、答え用の `"Tutorial\\Blog\\": "docs/tutorial/src/Blog/"` が既に登録されている。これはそのまま残してよい。自分のコードは `MyBlog\` という別名前空間なので、答えの登録と共存しても衝突しない。

### Step 4. スキーマ

`mywork/schema.sql`:

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

`mywork/sql/article_list.sql`:

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

`mywork/blog/ArticleQueryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace MyBlog;

use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleQueryInterface
{
    #[DbQuery('article_list')]
    public function list(): array;
}
```

### Step 3. `run.php` を作る

`mywork/run.php`:

```php
<?php

declare(strict_types=1);

namespace MyBlog;

use Aura\Sql\ExtendedPdoInterface;
use Composer\Autoload\ClassLoader;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;

/** @var ClassLoader $loader */
$loader = require dirname(__DIR__) . '/vendor/autoload.php'; // mywork/run.php → リポジトリの vendor
$loader->addPsr4('MyBlog\\', __DIR__ . '/blog');             // 自分のコード（答えと別名前空間）

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

/** @var ArticleQueryInterface $articleQuery */
$articleQuery = $injector->getInstance(ArticleQueryInterface::class);

var_dump($articleQuery->list());
```

### 実行

```bash
php mywork/run.php
```

> ブートストラップの `dirname(__DIR__)` は「`mywork/run.php` の1つ上 = リポジトリのルート」を指す前提。`mywork/` をリポジトリ外や別の階層に置く場合は、`dirname(__DIR__)` の部分をリポジトリの `vendor/autoload.php` への正しい相対・絶対パスに置き換える。

### 期待出力 (第1章 / 単独実行)

```text
array(1) {
  [0]=>
  array(7) {
    ["id"]=>
    int(1)
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

> 環境によって `id` が `string(1) "1"` で返ることもある (古い `PDO::ATTR_STRINGIFY_FETCHES` 設定や、`AuraSqlModule` の DSN オプション次第)。PHP 8.1+ かつ標準設定なら `int(1)` で返る。

### 解説

`ArticleQueryInterface` には実装クラスがない。にもかかわらず `$injector->getInstance(ArticleQueryInterface::class)` でインスタンスが取れる。これは Ray.Aop が `#[DbQuery]` 付きメソッドをインターセプトし、`article_list.sql` を読み込んで実行する「自動生成された実装」を返しているため。

- `#[DbQuery('article_list')]` の `'article_list'` は `sql/article_list.sql` のファイル名 (拡張子なし) と一致する。
- 戻り値型 `array` は「複数行の連想配列リスト」を意味する。型名で挙動が変わるのが Ray.MediaQuery のコア。
- カラム名は SQLite が返すままの snake_case (`author_name`, `published_at`)。第4章で Entity に hydrate する形に変え、第5章で hydration の経路を詳しく見る。

---

## 第2章: 単一行の取得

### ゴール

- 1行だけ返す SQL では `type: 'row'` を指定する
- 戻り値型 `?array` (= `array|null`) で、連想配列1つを直接受け取る (該当行がなければ `null`)

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
public function item(int $id): array|null;
```

### Step 3. `run.php` に追記

```php
$row = $articleQuery->item(1);
var_dump($row);
```

### 期待出力 (第2章 / 単独実行)

```text
array(7) {
  ["id"]=>
  int(1)
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
    string|null $publishedAt,
    string $createdAt,
): AffectedRows;
```

### Step 3. `run.php` に追記

```php
$affected = $articleQuery->add(
    title: 'Second',
    body: 'about SQL and Objects',
    authorName: 'Bob',
    status: 'published',
    publishedAt: '2026-04-02 10:00:00',
    createdAt: '2026-04-02 10:00:00',
);
printf("insert affected=%d\n", $affected->count);
var_dump($articleQuery->list());
```

### 期待出力 (第3章 / 単独実行)

```text
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

namespace MyBlog;

final class Article
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $body,
        public readonly string $authorName,    // ← SELECT の 4 番目に渡る (次章で詳しく)
        public readonly string $status,
        public readonly string|null $publishedAt,
        public readonly string $createdAt,
    ) {
    }
}
```

### Step 2. インターフェースの戻り値型を**書き換える**

第1章の `list(): array` と第2章の `item(int $id): array|null` を、戻り値型だけ書き換える。シグネチャはそのまま。

```php
/** @return array<Article> */
#[DbQuery('article_list')]
public function list(): array;

#[DbQuery('article_item', type: 'row')]
public function item(int $id): Article|null;
```

### Step 3. `run.php` を書き換える

> **断片の組み立てについて**: 各章のスニペットは `run.php` への追記・置換の**断片**であり、それ単体で完結した `run.php` ではない。組み立てるときは「INSERT などの書き込みは、それを読み出す `list()` / `item()` より**前**に置く」ことだけ意識すればよい。完成した通し `run.php` の一例は答え [`docs/tutorial/src/run.php`](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/docs/tutorial/src/run.php) にあるが、章ごとの解説順とは構成が異なる（章番号でまとめ直してある）ので、写経中の自分の `run.php` と1行ずつ一致はしない。

第1章で `var_dump($articleQuery->list())` していた箇所と、第2章で `var_dump($row)` していた箇所を、Entity を使う形に置き換える。

```php
$articles = $articleQuery->list();
foreach ($articles as $a) {
    printf("[%d] %s by %s\n", $a->id, $a->title, $a->authorName);
}

$first = $articleQuery->item(1);
echo $first?->title, "\n";
```

### 期待出力 (第4章 / 単独実行)

```text
[1] Hello by Alice
[2] Second by Bob
Hello
```

### 解説

- 戻り値型 `Article|null` (単一) や docblock `@return array<Article>` (複数) を見て、フレームワークが `Article` を組み立てる。
- 今回の `Article` は constructor を持つので、`FetchNewInstance` が選ばれて `PDO::FETCH_FUNC` で組み立てられる。**SELECT カラム順が constructor 引数順にそのまま渡される**。詳細は次章。
- Constructor Promotion のおかげで getter / setter は不要。`readonly` で意図せぬ変更を防ぐ。
- PHP 8.4 以降なら `final readonly class Article { ... }` と書けばさらに簡潔。

---

## 第5章: Constructor hydration と SELECT カラム順

### ゴール

- 第4章で動いた hydration の中身を知る。**何が契約になっているのか** を理解する。
- 「constructor あり」「constructor なし」で hydration 経路が変わることを知る。
- snake_case カラムを camelCase プロパティで受けたい場合は SQL alias が必要なことを知る。

### この章のポイント

`Article` のように constructor を持つ Entity を返したとき、Ray.MediaQuery は `PDO::FETCH_FUNC` を使い、SELECT 結果の各カラム値を **左から順に** constructor の引数に渡す (`FetchNewInstance`)。

```sql
-- article_list.sql
SELECT id, title, body, author_name, status, published_at, created_at
FROM article
```

```php
final class Article
{
    public function __construct(
        public readonly int $id,           // ← SELECT の 1 番目
        public readonly string $title,     // ← 2 番目
        public readonly string $body,      // ← 3 番目
        public readonly string $authorName,// ← 4 番目 (DB は author_name だが順序で渡るので名前は不問)
        public readonly string $status,
        public readonly string|null $publishedAt,
        public readonly string $createdAt,
    ) {}
}
```

**カラム名と引数名が偶然一致するから動いているのではない。** SQL の `SELECT id, title, body, author_name, ...` の順序と `__construct(int $id, string $title, string $body, string $authorName, ...)` の順序が一致しているから動いている。仮に SQL を `SELECT title, id, ...` のように入れ替えると、`$id` に title 文字列が、`$title` に id 整数が渡って TypeError になる。

### 試してみる: わざと壊す

`sql/article_list.sql` の `SELECT` 順を意図的に入れ替えると壊れることを 1 度だけ体験するとよい (体験したら戻す)。

```sql
-- 壊れる例
SELECT title, id, body, author_name, status, published_at, created_at
FROM article;
```

```text
TypeError: MyBlog\Article::__construct(): Argument #1 ($id) must be of type int, string given
```

### constructor を持たない Entity の場合

constructor を持たない Entity を返したときは `FetchClass` 経路 (`PDO::FETCH_CLASS`) になる。この経路は PDO が **カラム名と同名のプロパティ** に値を代入する。フレームワーク側で snake_case → camelCase に変換する処理は入っていない。

```php
// constructor を持たない Entity の例
final class ArticleBag
{
    public string $id;
    public string $title;
    public string $author_name;     // ← カラム名と同名
    public string|null $published_at;   // ← カラム名と同名
    // ...
}
```

PHP 側のプロパティを camelCase にしたい場合は、SQL 側で alias を付ける:

```sql
SELECT id, title, author_name AS authorName, published_at AS publishedAt
FROM article
```

ただし、現代的な PHP では readonly + constructor promotion で immutable な Entity を書きたいことが多く、結果的に `FetchNewInstance` (順序ベース) を選ぶことになる。**だから「SELECT カラム順 = constructor 引数順」が事実上の運用契約** になる。

### 解説

- どちらの hydration 経路を使うかは `FetchFactory::factory()` が戻り値型と Entity の constructor 有無を見て自動選択する。実装は `src/FetchFactory.php`。
- 経路は 5 つ (hydration 3 種 + factory 2 種): `FetchAssoc` (Entity なし) / `FetchClass` (Entity あり, constructor なし) / `FetchNewInstance` (Entity あり, constructor あり) / `FetchStaticFactory` / `FetchInjectionFactory` (どちらも `factory:` 属性あり、第7-8章)。
- `factory:` 属性を使うときも `PDO::FETCH_FUNC` ベース = **SELECT カラム順がそのまま factory メソッドの引数順** に渡る (第7章で詳しく)。
- 順序ベースの契約に怯える必要はない。SQL を変更したら戻り値型 (Entity) もセットで見直す習慣をつければ、IDE と PHPStan / Psalm が型ミスを早く拾う。

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

namespace MyBlog;

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

### Step 2. インターフェースを**書き換える**

この章では `DateTimeInterface` の自動変換に集中するため、`add()` の戻り値をいったん `void` にする。第10章で `InsertedRow` に戻し、同じ INSERT から id と変換後の値を取り出す。

`item()` も `int $id` → `ArticleId $id` に書き換える。

```php
use DateTimeInterface;

#[DbQuery('article_item', type: 'row')]
public function item(ArticleId $id): Article|null;

#[DbQuery('article_add')]
public function add(
    string $title,
    string $body,
    string $authorName,
    string $status = 'draft',
    DateTimeInterface|null $publishedAt = null,
    DateTimeInterface|null $createdAt = null,
): void;
```

### Step 3. `run.php` を**書き換える**

ここで第3章 / 第4章で書いた `run.php` の呼び出しを、新しいシグネチャに合わせて書き換える。

- 第3章で書いた `$affected = $articleQuery->add(...); printf("insert affected=%d\n", $affected->count);` は、`add(): void` に変わったので、**`$affected->count` を見る行は削除**する (もしくは `$articleQuery->add(...)` だけにする)。
- 第3章の `Second` の `add()` 呼び出しは `publishedAt: '2026-04-02 10:00:00'` のように**文字列**で日付を渡していた。`add()` の引数が `DateTimeInterface` 型になったので、`publishedAt: new DateTimeImmutable('2026-04-02 10:00:00')`（`createdAt` も同様）に変換する。**さもないと TypeError になる**。
- 第4章で書いた `$articleQuery->item(1)` は、`item(ArticleId $id)` に変わったので、`$articleQuery->item(new ArticleId(1))` に置き換える。

```php
use DateTimeImmutable;

$articleQuery->add(
    title: 'Third',
    body: 'about DateTime',
    authorName: 'Carol',
    status: 'published',
    publishedAt: new DateTimeImmutable('2026-04-03 11:00:00'),
    createdAt: new DateTimeImmutable('2026-04-03 11:00:00'),
);

$article = $articleQuery->item(new ArticleId(3));
var_dump($article->publishedAt);
```

### 期待出力 (第6章 / 単独実行)

```text
string(19) "2026-04-03 11:00:00"
```

### 解説

- **DateTime → 文字列**: `ParamConverter` が `DateTimeInterface` を検出し、`'Y-m-d H:i:s'` 形式の文字列に変換してから PDO に渡す。
- **ToScalarInterface**: `ArticleId::toScalar()` の返り値 (int) がそのまま `:id` にバインドされる。「コードの中では型安全な値オブジェクトとして扱い、SQL 境界で自動的にスカラーに変換」というパターン。
- **`null` 既定値の罠**: `DateTimeInterface|null = null` のように既定値が `null` の場合、引数を**省略**すると Ray.Di から `DateTimeInterface` 実装 (現在時刻) が注入される (`ParamInjector`)。
  - **「省略 = DB に NULL が入る」ではない**。`DateTimeInterface|null = null` は「**NULL 許容な型 + Ray.Di 用のデフォルト**」という意味で、省略時は ParamInjector が現在時刻に解決する。draft 記事のつもりで `publishedAt` を省略すると、ちゃんと `published_at` 列に値が入ってしまう。
  - 本当に NULL を保存したい場合は、`publishedAt: null` を**明示的に**渡すか、別の SQL / メソッドに分ける。
  - このチュートリアルでは第10章で `InsertedRow::$values` を使って、注入後・変換後の値を観測する。

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

namespace MyBlog;

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

> 以降、`namespace MyBlog;` から始まる部分スニペットは `<?php` / `declare(strict_types=1);` のファイルヘッダを省略している。実ファイル (`mywork/blog/ArticleStatsFactory.php` など) では先頭に付けること。また、`factory:` で指定するクラスは**必ず独立した PHP ファイル**に置く。`run.php` 内にインラインで定義すると、フレームワークが生成する実装クラスがそのファイルを再度 `require` して `Cannot redeclare class` になる。

```php
namespace MyBlog;

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
$stats = $articleQuery->stats(new ArticleId(1));
var_dump($stats);
```

### 期待出力 (第7章 / 単独実行・この時点)

```text
object(MyBlog\ArticleStats)#... {
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

namespace MyBlog;

use function mb_strlen;
use function mb_substr;
use function strip_tags;
use function trim;

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
namespace MyBlog;

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

### BDR の核心: DI が不可欠な例

`age`（年齢）はデータベースのカラムではない。`birth_date` と現在時刻から計算する — 現在時刻はファクトリの外から `DateTimeInterface` として注入するしかない。

`age`（年齢）はデータベースのカラムではない。`birth_date` と「現在時刻」の2つから計算する。現在時刻はファクトリの外から注入するしかない — `DateTimeInterface` として DI で受け取る。

`mywork/schema.sql` に `author` テーブルを追加:

```sql
CREATE TABLE IF NOT EXISTS author (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    birth_date TEXT NOT NULL
);
```

`sql/author_profile.sql`:

```sql
SELECT
    id,
    name,
    birth_date
FROM author
WHERE id = :id;
```

`Blog/AuthorProfile.php`:

```php
final class AuthorProfile
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $birthDate,
        public readonly int $age,        // カラムではない — ファクトリが計算する
    ) {}
}
```

`Blog/AuthorProfileFactory.php`:

```php
use DateTimeImmutable;
use DateTimeInterface;

final class AuthorProfileFactory
{
    public function __construct(
        private readonly DateTimeInterface $now,
    ) {}

    public function factory(int $id, string $name, string $birthDate): AuthorProfile
    {
        $age = (new DateTimeImmutable($birthDate))->diff($this->now)->y;

        return new AuthorProfile(
            id: $id,
            name: $name,
            birthDate: $birthDate,
            age: $age,
        );
    }
}
```

`Blog/AuthorQueryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace MyBlog;

use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorQueryInterface
{
    #[DbQuery('author_profile', type: 'row', factory: AuthorProfileFactory::class)]
    public function profile(int $id): AuthorProfile|null;
}
```

`Queries::fromClasses()` に `AuthorQueryInterface::class` を追加し、Module に `MarkdownExcerpter` の bind を加える:

```php
$this->bind(MarkdownExcerpter::class);
```

`DateTimeInterface` は `MediaQueryModule` 内部で既に `DateTimeImmutable` に bind されているため、追加の bind は不要。

`run.php` でデータを挿入して呼び出す:

```php
$pdo->perform('INSERT INTO author (name, birth_date) VALUES (?, ?)', ['Alice', '1990-06-15']);

/** @var AuthorQueryInterface $authorQuery */
$authorQuery = $injector->getInstance(AuthorQueryInterface::class);
$profile = $authorQuery->profile(1);
printf("name=%s birth_date=%s age=%d\n", $profile->name, $profile->birthDate, $profile->age);
```

### 期待出力 (第8章 / BDR フォーカス / 単独実行)

```text
name=Alice birth_date=1990-06-15 age=35
```

> `age` はクエリ境界でファクトリが `birth_date` と `DateTimeInterface $now` から計算する。35 という値は `birth_date = '1990-06-15'` と実行日 2026-06-06 の組み合わせで、毎年変わる。`MediaQueryModule` が `DateTimeInterface` を `DateTimeImmutable` に bind しているため追加設定は不要（注入のたびに `new DateTimeImmutable()` が生成され、常に現在時刻が渡る）。テストでは固定インスタンスで上書き bind すれば `age` を決定的にできる。

コントローラーとテンプレートは `$profile->age` と書くだけで値が手に入る — クエリ境界の外での計算はゼロ。これが BDR の本質: **エンティティは受け取った時点で完成している**。

### Step 4. Comment 関連のファイルを足す

stats を意味あるものにするためにコメントが要る。ここで Comment Entity と CommentQueryInterface を作って、`add()` / `listFor()` の2メソッドで運用する。`listFor()` は第1章の `list()` と同じ `array<Comment>` 型を返すので、Entity hydration の復習にもなる。

`Blog/Comment.php`:

```php
<?php

declare(strict_types=1);

namespace MyBlog;

final class Comment
{
    public function __construct(
        public readonly int $id,
        public readonly int $articleId,
        public readonly string $body,
        public readonly string $postedAt,
    ) {
    }
}
```

`sql/comment_add.sql`:

```sql
INSERT INTO comment (article_id, body, posted_at)
VALUES (:articleId, :body, :postedAt);
```

`sql/comment_list.sql`:

```sql
SELECT
    id,
    article_id,
    body,
    posted_at
FROM comment
WHERE article_id = :articleId
ORDER BY id;
```

`Blog/CommentQueryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace MyBlog;

use DateTimeInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\InsertedRow;

interface CommentQueryInterface
{
    #[DbQuery('comment_add')]
    public function add(
        int $articleId,
        string $body,
        DateTimeInterface|null $postedAt = null,
    ): InsertedRow;

    /** @return array<Comment> */
    #[DbQuery('comment_list')]
    public function listFor(int $articleId): array;
}
```

### Step 5. `run.php` で `commentQuery` を使う

`run.php` の `Queries::fromClasses()` に `CommentQueryInterface::class` を追記し、`$commentQuery` を取得する。

```php
$queries = Queries::fromClasses([
    ArticleQueryInterface::class,
    CommentQueryInterface::class,
]);

/** @var CommentQueryInterface $commentQuery */
$commentQuery = $injector->getInstance(CommentQueryInterface::class);
```

コメントを追加してから、`stats()` で `commentCount` を確認し、`listFor()` で `Comment` hydration も確認する。

```php
$commentQuery->add(1, 'Great post!', new DateTimeImmutable('2026-04-01 12:00:00'));
$commentQuery->add(1, 'Thanks!',     new DateTimeImmutable('2026-04-01 13:00:00'));

$stats = $articleQuery->stats(new ArticleId(1));
printf("commentCount=%d, excerpt='%s'\n", $stats->commentCount, $stats->excerpt);

$comments = $commentQuery->listFor(1);
printf("comments=%d, first body='%s' (id=%d)\n", count($comments), $comments[0]->body, $comments[0]->id);
```

### 期待出力 (第8章 / 統合 run.php)

> ここからは Article の本文や件数が前章までの累積に依存するため、完成版 `run.php` を通しで実行したときの値を示す。`excerpt` の本文は統合 `run.php` が最初に投入する Article のものである。

```text
commentCount=2, excerpt='This is the first post about interface-driven SQL.'
comments=2, first body='Great post!' (id=1)
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
$updated = $articleQuery->update(new ArticleId(1), 'Hello (edited)', 'updated body');
printf("updated count=%d, isAffected=%s\n", $updated->count, $updated->isAffected() ? 'yes' : 'no');

$deleted = $articleQuery->delete(new ArticleId(2));
printf("deleted count=%d\n", $deleted->count);
```

### 期待出力 (第9章 / 統合 run.php)

```text
updated count=1, isAffected=yes
deleted count=1
```

### 解説

- `AffectedRows` は `final class` で、`readonly` な `int $count` プロパティと `isAffected(): bool` メソッドだけを持つ。
- 戻り値型に `AffectedRows` と書くだけで、フレームワークが `$statement->rowCount()` を呼んで構築してくれる。
- 第3章の INSERT と同じく、SQL の種類をフレームワークに推測させるのではなく、戻り値型で「何を知りたいか」を宣言する。

---

## 第10章: INSERT で id と確定値を得る

### ゴール

- Ray.MediaQuery 1.1 で追加された `InsertedRow` 戻り値で、自動採番された `id` と「フレームワークが解決して DB に渡した値」を取り出す。
- 第3章の `AffectedRows` では足りない場面で、INSERT 専用の結果型を選ぶ判断基準を知る。

### Step 1. インターフェースを**書き換える**

第3章では `AffectedRows`、第6章では `void` だった `add()` を、ここで `InsertedRow` に書き換える。これがこの tutorial における `add()` の完成形である。

```php
use Ray\MediaQuery\Result\InsertedRow;

#[DbQuery('article_add')]
public function add(
    string $title,
    string $body,
    string $authorName,
    string $status = 'draft',
    DateTimeInterface|null $publishedAt = null,
    DateTimeInterface|null $createdAt = null,
): InsertedRow;
```

### Step 2. `run.php` を**書き換える**

第6章で `$articleQuery->add(...)` (戻り値を捨てる) と書いていた箇所を、戻り値を `$inserted` で受けて id と values を見る形に置き換える。

```php
$inserted = $articleQuery->add(
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

このメソッド定義では、`publishedAt` や `createdAt` を省略すると、`ParamInjector` が `DateTimeInterface` (現在時刻) を注入し、`ParamConverter` が SQL 用の文字列に変換する。**「省略 = NULL」ではない**点に注意 (第6章参照)。NULL を保存したい場合は `publishedAt: null` を明示的に渡す。

```php
$draft = $articleQuery->add(
    title: 'Draft',
    body: 'createdAt is injected',
    authorName: 'Dana',
);

var_dump($draft->values['createdAt']);
```

### 期待出力 (第10章 / 統合 run.php)

> 統合 `run.php` ではこの `add('Hello', ...)` が最初の INSERT なので `id=1` になる。自分で写経した `run.php` で第1章以降の INSERT を積み上げている場合は、その分だけ大きい `id` が返る。

```text
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
#[DbQuery('article_paginated')]
#[Pager(perPage: 10)]
public function paginated(): Pages;
```

### Step 3. `run.php` でデータを増やす

```php
for ($i = 3; $i <= 32; $i++) {
    $articleQuery->add(
        title: "Post #{$i}",
        body: "Body for post {$i}.",
        authorName: 'Carol',
        status: 'published',
        publishedAt: new DateTimeImmutable('2026-04-03 00:00:00'),
        createdAt:   new DateTimeImmutable('2026-04-03 00:00:00'),
    );
}

$pages = $articleQuery->paginated();
$page1 = $pages[1];

printf("total items=%d\n", count($pages));
printf("page 1 has %d items, hasNext=%s\n", count($page1->data), $page1->hasNext ? 'yes' : 'no');
echo $page1->data[0]->title, "\n";
```

### 期待出力 (第11章 / paginated)

```text
total items=31
page 1 has 10 items, hasNext=yes
Hello, Ray.MediaQuery (edited)
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
#[DbQuery('article_stats_paginated', factory: ArticleStatsFactory::class)]
#[Pager(perPage: 10)]
public function statsPaginated(): Pages;
```

`run.php` で確認する:

```php
$statsPages = $articleQuery->statsPaginated();
$statsPage1 = $statsPages[1];
$firstStats = $statsPage1->data[0];

printf(
    "first stats row=%s commentCount=%d excerpt='%s'\n",
    $firstStats::class,
    $firstStats->commentCount,
    $firstStats->excerpt,
);
```

### 期待出力 (第11章 / statsPaginated)

```text
first stats row=MyBlog\ArticleStats commentCount=2 excerpt='Updated body.'
```

### 解説

- `count($pages)` は **総アイテム数** (= COUNT クエリの結果)。総ページ数ではないので注意。
- `$pages[1]` でページ1にアクセス → SELECT に LIMIT/OFFSET が付いて実行される (lazy)。
- `$page->data` は Article のリスト (`@return Pages<Article>` のおかげで hydration が効く)。
- `#[DbQuery(factory: ArticleStatsFactory::class)]` と `#[Pager]` を併用した場合、1.1 以降は `$page->data` の各行も `ArticleStatsFactory` で作られる。
- ここでも第7章と同じく、**factory メソッドの引数は SELECT カラム順** で渡される (`PDO::FETCH_FUNC`)。`article_stats_paginated.sql` の `SELECT a.id, a.title, a.body, comment_count, a.status` の順序と `ArticleStatsFactory::factory(int $id, string $title, string $body, int $commentCount, string $status)` の引数順が一致しているから動いている。Pager を被せても契約は変わらない。
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

まず、結果クラスが投げる専用例外を用意する。汎用の `UnexpectedValueException` を直接使わず、ドメイン専用の例外型にしておくと、呼び出し側が `catch` で意図を限定できる。

`Blog/Exception/UnexpectedRowException.php`:

```php
<?php

declare(strict_types=1);

namespace MyBlog\Exception;

use UnexpectedValueException;

final class UnexpectedRowException extends UnexpectedValueException
{
}
```

`Blog/ArticleSearchResult.php`:

```php
<?php

declare(strict_types=1);

namespace MyBlog;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use MyBlog\Exception\UnexpectedRowException;

use function count;

/** @template T of Article */
final class ArticleSearchResult implements PostQueryInterface
{
    /** @param list<T> $rows */
    public function __construct(
        public readonly array $rows,
        public readonly int $matched,
        public readonly string $sql,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $matched = count($context->rows);
        $rows = [];
        foreach ($context->rows as $row) {
            if (! $row instanceof Article) {
                throw new UnexpectedRowException('ArticleSearchResult expects Article rows.');
            }

            $rows[] = $row;
        }

        return new static(
            rows: $rows,
            matched: $matched,
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
$result = $articleQuery->search('%Post%');
printf("matched=%d, sql contains 'LIKE'=%s\n", $result->matched, str_contains($result->sql, 'LIKE') ? 'yes' : 'no');
echo "First hit: ", $result->rows[0]->title, "\n";
```

> `$result->sql`（= `$context->statement->queryString`）は、実行のために書き換えられた後の SQL である。Aura.Sql は同じ名前付きプレースホルダが複数回現れると 2 つ目以降を `:keyword__1` のように別名へ書き換え、さらに multi-statement を分解する都合で末尾の `;` も落ちる。そのため SQL ファイルの文字列との**完全一致比較は避け**、ここでは `str_contains($result->sql, 'LIKE')` のような部分一致で確認している。

### 期待出力 (第12章 / 統合 run.php)

```text
matched=30, sql contains 'LIKE'=yes
First hit: Post #3
```

### 解説

- `PostQueryInterface` の唯一の契約は `static fromContext(PostQueryContext): static`。
- `PostQueryContext` には次の情報が入る:
  - `$context->statement` (`PDOStatement`) — `rowCount()`, `queryString` など（`queryString` は実行用に書き換えられた後の SQL で、元の SQL ファイルと完全一致するとは限らない）
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

> この章は `run.php` から実行しない解説章なので、ここで書く `UnsupportedQueryException` と `FakeArticleQuery` は答えコード (`docs/tutorial/src/`) には含めていない（統合 `run.php` が使わないため）。自分の `mywork/` 配下に写経して動かしてほしい。答えに収録されているドメイン例外は、第12章・補章の結果クラスが実際に使う `Blog/Exception/UnexpectedRowException.php` のみである。

### Step 1. Fake 実装を書く

Fake が「このテストでは呼ばれない」メソッドで投げる例外も、汎用の `\LogicException` ではなくドメイン専用にしておく。

> この Fake は完成形の `ArticleQueryInterface` 全体を実装するため、補章で扱う `createAndGet()` も含まれている。補章をまだ読んでいない場合は、`createAndGet()` の行はいったん読み飛ばしてよい。

> Fake クラスは `mywork/blog/Test/FakeArticleQuery.php` に置き、名前空間は `MyBlog\Test`（PSR-4 で `mywork/blog/Test/` に対応）にする。

`Blog/Exception/UnsupportedQueryException.php`:

```php
<?php

declare(strict_types=1);

namespace MyBlog\Exception;

use LogicException;

final class UnsupportedQueryException extends LogicException
{
}
```

```php
namespace MyBlog\Test;

use DateTimeInterface;
use MyBlog\Article;
use MyBlog\ArticleId;
use MyBlog\ArticleQueryInterface;
use MyBlog\ArticleSearchResult;
use MyBlog\ArticleStats;
use MyBlog\CreatedArticle;
use MyBlog\Exception\UnsupportedQueryException;
use Ray\MediaQuery\Pages;
use Ray\MediaQuery\Result\AffectedRows;
use Ray\MediaQuery\Result\InsertedRow;

final class FakeArticleQuery implements ArticleQueryInterface
{
    /** @var array<int, Article> */
    private array $store = [];

    public function list(): array { return array_values($this->store); }
    public function item(ArticleId $id): Article|null { return $this->store[$id->value] ?? null; }

    public function add(string $title, string $body, string $authorName, string $status = 'draft', DateTimeInterface|null $publishedAt = null, DateTimeInterface|null $createdAt = null): InsertedRow
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
    public function paginated(): Pages { throw new UnsupportedQueryException('not used in this test'); }
    public function statsPaginated(): Pages { throw new UnsupportedQueryException('not used in this test'); }
    public function stats(ArticleId $id): ArticleStats { throw new UnsupportedQueryException('not used'); }
    public function search(string $keyword): ArticleSearchResult { throw new UnsupportedQueryException('not used'); }
    public function createAndGet(string $title, string $body, string $authorName, string $status = 'draft', DateTimeInterface|null $createdAt = null): CreatedArticle
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

/** @var ArticleQueryInterface $articleQuery */
$articleQuery = $injector->getInstance(ArticleQueryInterface::class);
$articleQuery->add('T', 'B', 'A');
assert($articleQuery->item(new ArticleId(1))->title === 'T');
```

> `assert()` は PHP CLI の既定設定 (`zend.assertions=-1`) では**コンパイル時に除去され、何も検証しない**。この場で動作確認するなら `php -d zend.assertions=1 mywork/run.php` のように有効化して実行する。実プロジェクトでは下記のとおり PHPUnit のアサーションを使うのが本筋。

### 解説

- DB を使わずにロジックの単体テストができる。
- `tests/Fake/Queries/` には Ray.MediaQuery 自身のテストで使われている Fake interface 群が大量にある。「実プロジェクトでは PHPUnit でこう書く」の参考に良い。
- PHPUnit を使う場合は `composer require --dev phpunit/phpunit` 後、`PHPUnit\Framework\TestCase` を継承して同じ Module 差し替えパターンを使う（`assertSame('T', ...)` のように PHPUnit のアサーションを使えば、上記の `zend.assertions` 設定に依存しない）。

---

## 補章: Multi-statement DML + SELECT

ここまでで Ray.MediaQuery の主要機能は一通り扱った。最後にもう一つ、1.1 で SELECT 結果まで受け取れるようになった `PostQueryInterface` を使って、INSERT と直後の SELECT を **1つのメソッド契約** で表現する例を見ておく。

### ゴール

- 同じ SQL ファイルに複数 statement (INSERT → SELECT) を書く。
- 1.1 の `PostQueryContext::$rows` を使って、SELECT 結果を hydrate された Entity として受け取る自作 `PostQueryInterface` を作る。

### Step 1. SQL

「記事を作成し、その作成済み行を返す」という use case は、Repository 実装では INSERT、last insert id の取得、SELECT、hydrate を手書きしがちである。Ray.MediaQuery では、この一連の interaction を SQL と戻り値型で宣言できる。

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

namespace MyBlog;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use MyBlog\Exception\UnexpectedRowException;

/** @template T of Article */
final class CreatedArticle implements PostQueryInterface
{
    /** @param T $article */
    public function __construct(
        public readonly Article $article,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $article = $context->rows[0] ?? null;
        if (! $article instanceof Article) {
            throw new UnexpectedRowException('CreatedArticle expects the final SELECT to return an Article row.');
        }

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
    DateTimeInterface|null $createdAt = null,
): CreatedArticle;
```

`run.php`:

```php
$created = $articleQuery->createAndGet(
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

### 期待出力 (補章 / 統合 run.php)

```text
created article id=33 title='Created and fetched' status=draft
```

### 解説

- SQL ファイル内の statement は `;` で分解される。`createAndGet()` は INSERT → SELECT の **2 statement を1メソッドの呼び出し** で実行している。
- `PostQueryContext::$rows` は **最後の statement が SELECT のとき**、その hydrated 結果を保持する (最後が DML なら `[]`)。
- 自作 `CreatedArticle::fromContext()` の中で `$context->rows[0]` を `Article` インスタンスとして取り出している。`ArticleQueryInterface::createAndGet()` の docblock `@return CreatedArticle<Article>` がこの hydration のヒントになる。
- このパターンは「INSERT して直後に SELECT する」「UPDATE 後に最新行を返す」など、DML の確定値をすぐ次に使う場面で型付きの interaction を表現できる。

重要なのは、`createAndGet()` が「ArticleRepository の便利メソッド」ではなく、「記事を作成して、その作成結果を型付きで返す Query 契約」になっている点である。

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

> **チュートリアルでの簡略化**: このハンズオンでは説明を簡単にするため、`list` / `item` / `add` / `update` / `delete` / `paginated` / `statsPaginated` / `stats` / `search` / `createAndGet` を1つの `ArticleQueryInterface` に集めている。実プロジェクトでは `ArticleSearchQueryInterface`、`ArticleStatsQueryInterface`、`ArticleCommandInterface` のように use case 単位で分けると、Fake も小さく、責務も明確になる。

補章で見た `PostQueryInterface` による DML + SELECT は、この Query-first をもう一段進めるものである。Write 側の Aggregate 永続化には Repository、Read 側や Projection 取得・DML 後の型付き結果取得には Query-first ── Ray.MediaQuery は、この後者を interface と SQL で明示するための仕組みである。

---

## 完走おめでとう

ここまで読み終えると、Ray.MediaQuery の主要機能を一通り体験したことになる。

このハンズオンは「interface + SQL + 戻り値型でアプリケーションの Query 契約を作る」理解を優先している。以下は本文では実装せず、Manual で確認する発展機能である。

- `#[Input]` Object Flattening — 入力 DTO を SQL パラメータへ平坦化する。
- `SqlQueryInterface` 直接実行 — interface 経由ではなく、低レベル API として SQL を実行する。
- `#[Pager(perPage: 'perPage')]` — メソッド引数でページサイズを動的に変える。
- `MediaQuerySqlModule` — interface ディレクトリから Query interface を自動発見する簡易 module。
- `SqlTemplate` / `MediaQuerySqlTemplateModule` — SQL 実行テンプレートを差し替える高度な設定。

### 次に読むもの

- [BDR パターン集](bdr-patterns.ja.md) — 行ごとの加工（`factory:`）と結果セット全体の成形（`PostQueryInterface`）: バッジ・enum・JOINグルーピング・ソート・SPLイテレータ・Null Object
- [BDR Pattern Guide 日本語版](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN-ja.md) — ファクトリパターンとドメインオブジェクトの設計
- [Manual](https://ray-di.github.io/Ray.MediaQuery/reference/) — マニュアル (`#[Input]` Object Flattening, `SqlQueryInterface` 直接実行などの応用)
- [llms-full.txt](../llms-full.txt) — AI エージェント向けの圧縮リファレンス
- [`tests/Fake/`](https://github.com/ray-di/Ray.MediaQuery/tree/1.x/tests/Fake) — 実際のテストコード

### コミュニティ

- [Issues](https://github.com/ray-di/Ray.MediaQuery/issues)
- [BEAR.Sunday](https://bearsunday.github.io/) — Ray.MediaQuery を内蔵するアプリケーションフレームワーク
