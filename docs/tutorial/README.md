---
layout: default
title: Ray.MediaQuery Hands-on Tutorial
description: A hands-on introduction that builds a SQLite blog service while covering the main Ray.MediaQuery features through 1.1.0.
lang: en
permalink: /tutorial/
---

# Ray.MediaQuery Hands-on Tutorial

This tutorial builds a small blog service and walks through the major Ray.MediaQuery features through 1.1.0.

- Requirements: PHP 8.2+, Composer, basic SQL, and a basic understanding of DI
- Database: SQLite (`:memory:`), so no additional database server is required

[日本語版はこちら](https://ray-di.github.io/Ray.MediaQuery/tutorial/ja/)

## How to Read This Tutorial

Each chapter follows the same flow.

1. **Goal** - what you will be able to do in the chapter
2. **Steps** - write SQL, then an interface, then add code to `run.php`
3. **Run and expected output** - execute your working `run.php` with `php mywork/run.php`
4. **Explanation** - what the framework is doing
5. **Next chapter**

The completed source is available under [`docs/tutorial/src/`](https://github.com/ray-di/Ray.MediaQuery/tree/1.x/docs/tutorial/src). Treat it as the answer key when you get stuck.

> **About the completed `run.php`**: [`docs/tutorial/src/run.php`](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/docs/tutorial/src/run.php) is an integrated demo that runs the whole tutorial. Expected output sections are labeled either `(standalone)` or `(integrated run.php)`.
>
> - `(standalone)` means the output from a small `run.php` containing only the code for that chapter. The early feature introductions use this label.
> - `(integrated run.php)` means the output from the completed demo after all previous inserts, updates, and deletes have accumulated. Later chapters depend on that state.
>
> Your own `run.php` may produce different ids and counts depending on which chapters you have accumulated. Check the type and structure, not just the exact number. The completed `run.php` executes chapters 1 through 12 plus the appendix. Chapter 13 is a testing-strategy chapter and has no demo code.

This tutorial intentionally rewrites some method declarations as it progresses. For example, `add()` returns `AffectedRows` in chapter 3, `void` in chapter 6, and the final `InsertedRow` shape from chapter 10 onward. You will experience the intermediate forms first, then converge on the final interface.

The tutorial assumes **Ray.MediaQuery 1.1.0 or later**. These 1.1.0 features are covered hands-on, so the later chapters do not work unchanged on the 1.0 series.

- Typed result construction through `PostQueryInterface` for both DML and SELECT results
- `AffectedRows` / `InsertedRow`
- `#[Pager]` used together with `#[DbQuery(factory: ...)]` so rows inside a page are hydrated through the factory

You do not need to complete everything at once. To get the implementation feel, chapters 0 through 6 are enough. To understand the 1.1 additions, read chapters 9 through 12 and the conclusion.

## Return Type Cheat Sheet

In Ray.MediaQuery, the **method return type** is the contract that tells the framework how to handle the result. The SQL kind is not the only signal.

| Return type / docblock | Meaning |
|------------------------|---------|
| `array` | Return multiple rows as a list of associative arrays |
| `?array` + `type: 'row'` | Return one associative row, or `null` if no row matches |
| `/** @return array<Article> */ array` | Hydrate multiple rows into `Article` objects |
| `?Article` + `type: 'row'` | Return one `Article` object, or `null` if no row matches |
| `void` | Execute DML and ignore the result |
| `AffectedRows` | Return the row count affected by INSERT / UPDATE / DELETE |
| `InsertedRow` | Return the auto-increment id and the resolved bound values after INSERT |
| `Pages<Article>` | Return a paginated `Article` list |
| `PostQueryInterface` implementation | Build a custom result object from `PostQueryContext` after execution |

The SQL examples use a readable "Holywell-lite" style.

- SQL keywords are uppercase.
- Table and column names use lowercase `snake_case`.
- Multi-column `SELECT` lists use one column per line.
- Indentation is 4 spaces.
- Aliases use explicit `AS`.
- SQL files in this tutorial end with `;`. In multi-statement SQL, every statement must end with `;`.
- Short `INSERT` / `DELETE` statements stay on 1-2 lines for copyability.

SQL placeholders are the exception: they match PHP argument names and therefore use camelCase, such as `:authorName`.

## Completed Directory Structure

The tree below is the completed answer under `docs/tutorial/src/` with namespace `Tutorial\Blog\`. Your own copied code goes under `mywork/` with namespace `MyBlog\` (see chapter 0). The namespaces differ, so both trees can coexist in the same repository.

```text
docs/tutorial/src/
|-- run.php                  # Entry point that runs all chapters
|-- schema.sql               # Table definitions
|-- Blog/
|   |-- Article.php
|   |-- ArticleQueryInterface.php
|   |-- Comment.php
|   |-- CommentQueryInterface.php
|   |-- ArticleId.php                # ToScalarInterface implementation
|   |-- ArticleStats.php
|   |-- ArticleStatsFactory.php      # DI factory
|   |-- MarkdownExcerpter.php        # Injected into the factory
|   |-- AuthorProfile.php            # `age` is not a column
|   |-- AuthorProfileFactory.php     # DI factory with an injected clock
|   |-- AuthorQueryInterface.php
|   |-- ArticleSearchResult.php      # SELECT PostQueryInterface
|   |-- CreatedArticle.php           # DML + SELECT PostQueryInterface
|   `-- Exception/
|       `-- UnexpectedRowException.php  # Domain exception thrown by result classes
`-- sql/
    |-- article_add.sql
    |-- article_create_and_get.sql
    |-- article_item.sql
    |-- article_list.sql
    |-- article_update.sql
    |-- article_delete.sql
    |-- article_paginated.sql
    |-- article_search.sql
    |-- article_stats.sql
    |-- article_stats_paginated.sql
    |-- author_profile.sql
    |-- comment_add.sql
    `-- comment_list.sql
```

## Table of Contents

| Chapter | Title | Feature |
|---------|-------|---------|
| [Chapter 0](#chapter-0-setup) | Setup | autoload, SQLite `:memory:` |
| [Chapter 1](#chapter-1-first-query-listing-rows) | First query: listing rows | `#[DbQuery]` / SELECT row list |
| [Chapter 2](#chapter-2-fetching-one-row) | Fetching one row | `#[DbQuery(type: 'row')]` |
| [Chapter 3](#chapter-3-insert-and-affectedrows) | INSERT and AffectedRows | INSERT / `AffectedRows` |
| [Chapter 4](#chapter-4-automatic-entity-mapping) | Automatic entity mapping | Constructor promotion / readonly |
| [Chapter 5](#chapter-5-constructor-hydration-and-select-column-order) | Constructor hydration and SELECT column order | `FetchNewInstance` / hydration path |
| [Chapter 6](#chapter-6-datetime-and-toscalar) | DateTime and ToScalar | `DateTimeInterface` / `ToScalarInterface` |
| [Chapter 7](#chapter-7-building-derived-values-with-a-factory) | Building derived values with a factory | `factory:` static factory |
| [Chapter 8](#chapter-8-injecting-dependencies-into-a-factory) | Injecting dependencies into a factory | `factory:` DI factory |
| [Chapter 9](#chapter-9-update--delete-and-affected-row-counts) | UPDATE / DELETE and affected row counts | `AffectedRows` |
| [Chapter 10](#chapter-10-getting-the-id-and-final-values-from-insert) | Getting id and final values from INSERT | `InsertedRow` |
| [Chapter 11](#chapter-11-pagination) | Pagination | `#[Pager]` / `Pages<Article>` / factory hydration |
| [Chapter 12](#chapter-12-custom-postqueryinterface) | Custom PostQueryInterface | SELECT-capable `PostQueryInterface::fromContext()` |
| [Chapter 13](#chapter-13-testing-strategy) | Testing strategy | fake bindings |
| [Appendix](#appendix-multi-statement-dml--select) | Multi-statement DML + SELECT | one method for INSERT + SELECT |
| [Conclusion](#conclusion-query-first-vs-repository-pattern) | Query-first vs Repository Pattern | query contracts and CQRS read models |

---

## Chapter 0: Setup

### Goal

- Create a working directory
- Prepare Composer autoloading
- Initialize an empty schema in an in-memory SQLite database

### Step 1. Clone the repository and install dependencies

```bash
php -m | grep '^pdo_sqlite$'
git clone https://github.com/ray-di/Ray.MediaQuery.git
cd Ray.MediaQuery
composer install
```

If `pdo_sqlite` is printed, you are ready.

### Step 2. Create the working tree

Write your code in `mywork/` with namespace `MyBlog\`, separate from the answer tree (`docs/tutorial/src/`, namespace `Tutorial\Blog\`). This avoids collisions and mirrors a real project where you install the library and write your own application code.

From the repository root:

```bash
mkdir -p mywork/blog mywork/sql
```

From now on, `Blog/Xxx.php` means `mywork/blog/Xxx.php`, `sql/xxx.sql` means `mywork/sql/xxx.sql`, and `run.php` means `mywork/run.php`.

### Step 3. Add autoloading to `composer.json`

Map the `MyBlog\` namespace to `mywork/blog/`. This is not strictly required for `run.php` because the bootstrap below calls `$loader->addPsr4()`, but it is useful for IDE completion and PHPUnit.

```json
{
    "autoload-dev": {
        "psr-4": {
            "MyBlog\\": "mywork/blog/"
        }
    }
}
```

Apply it with:

```bash
composer dump-autoload
```

> The repository's actual `composer.json` already contains `"Tutorial\\Blog\\": "docs/tutorial/src/Blog/"` for the answer code. Leave it as-is. Your code uses the separate `MyBlog\` namespace, so the two mappings can coexist.

### Step 4. Schema

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

### Explanation

- **`AUTOINCREMENT`**: chapter 10 uses `InsertedRow::$id`, so `id` is auto-generated from the start.
- **`published_at TEXT`**: SQLite has no native `DATETIME` type. Dates are stored as strings. Chapter 6 shows how `DateTimeImmutable` is converted into a `'Y-m-d H:i:s'` string.

In chapter 1, you start writing query code.

---

## Chapter 1: First Query: Listing Rows

### Goal

- Attach `#[DbQuery('id')]` to an interface method, place `id.sql`, and experience a working query with no implementation class.
- Use return type `array` to fetch multiple rows as associative arrays.

### Step 1. Write SQL

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

### Step 2. Write the interface

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

### Step 3. Create `run.php`

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
$loader = require dirname(__DIR__) . '/vendor/autoload.php'; // mywork/run.php -> repository vendor
$loader->addPsr4('MyBlog\\', __DIR__ . '/blog');             // your code, separate from the answer namespace

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

// Seed one row and list articles.
$pdo->perform(
    'INSERT INTO article (title, body, author_name, status, created_at) VALUES (?, ?, ?, ?, ?)',
    ['Hello', 'first body', 'Alice', 'published', '2026-04-01 09:00:00'],
);

/** @var ArticleQueryInterface $articleQuery */
$articleQuery = $injector->getInstance(ArticleQueryInterface::class);

var_dump($articleQuery->list());
```

### Run

```bash
php mywork/run.php
```

> The `dirname(__DIR__)` bootstrap assumes `mywork/run.php` is one level below the repository root. If you place `mywork/` elsewhere, replace that part with the correct path to the repository's `vendor/autoload.php`.

### Expected Output (chapter 1 / standalone)

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

> Depending on the environment, `id` may be returned as `string(1) "1"` instead of `int(1)` (for example with older `PDO::ATTR_STRINGIFY_FETCHES` settings or DSN options). On PHP 8.1+ with standard settings, `int(1)` is expected.

### Explanation

`ArticleQueryInterface` has no implementation class. Yet `$injector->getInstance(ArticleQueryInterface::class)` returns an instance. Ray.Aop intercepts methods marked with `#[DbQuery]`, reads `article_list.sql`, and supplies an automatically generated implementation.

- `#[DbQuery('article_list')]` maps to `sql/article_list.sql` without the extension.
- Return type `array` means "a list of associative rows". Ray.MediaQuery's core behavior is driven by the return type.
- Column names are the raw SQLite names such as `author_name` and `published_at`. Chapter 4 hydrates them into an Entity, and chapter 5 explains the hydration path.

---

## Chapter 2: Fetching One Row

### Goal

- Use `type: 'row'` for SQL that returns one row.
- Use return type `?array` (`array|null`) to receive one associative array, or `null` if no row matches.

### Step 1. Write SQL

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

### Step 2. Add a method to the interface

`Blog/ArticleQueryInterface.php`:

```php
#[DbQuery('article_item', type: 'row')]
public function item(int $id): array|null;
```

### Step 3. Add to `run.php`

```php
$row = $articleQuery->item(1);
var_dump($row);
```

### Expected Output (chapter 2 / standalone)

```text
array(7) {
  ["id"]=>
  int(1)
  ["title"]=>
  string(5) "Hello"
  ...
}
```

### Explanation

- `type: 'row'` selects single-row mode, similar to `fetch()`.
- The default is `type: 'row_list'`, similar to `fetchAll()`.
- Even for the same SQL file, the result shape changes according to the return type and `type` setting.

---

## Chapter 3: INSERT and AffectedRows

### Goal

- Use the Ray.MediaQuery 1.1 `AffectedRows` result to receive the row count from your first write query.
- Confirm that DML intent is also declared by the method return type.

### Step 1. Write SQL

`sql/article_add.sql`:

```sql
INSERT INTO article (title, body, author_name, status, published_at, created_at)
VALUES (:title, :body, :authorName, :status, :publishedAt, :createdAt);
```

### Step 2. Add a method to the interface

In this chapter, `add()` returns `AffectedRows`. Later chapters temporarily change it to `void`, then to its final `InsertedRow` form. The completed [`ArticleQueryInterface.php`](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/docs/tutorial/src/Blog/ArticleQueryInterface.php) contains the chapter 10 `InsertedRow` version.

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

### Step 3. Add to `run.php`

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

### Expected Output (chapter 3 / standalone)

```text
insert affected=1
array(2) {
  [0] => array(7) { ... "Hello" ... }
  [1] => array(7) { ... "Second" ... }
}
```

### Explanation

- Method argument names (`$title`, `$authorName`, and so on) are bound to SQL placeholders with the same names (`:title`, `:authorName`). Argument order does not matter.
- Return type `AffectedRows` declares "I want the DML affected row count." It works for INSERT, UPDATE, and DELETE.
- Chapter 10 changes the same INSERT to `InsertedRow` so you can receive both the auto-generated id and the resolved bound values.

---

## Chapter 4: Automatic Entity Mapping

### Goal

- Receive results as `Article` objects instead of associative arrays.
- Write immutable Entities with constructor property promotion and `readonly`.

### Step 1. Write the Entity

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
        public readonly string $authorName,    // receives the 4th SELECT column
        public readonly string $status,
        public readonly string|null $publishedAt,
        public readonly string $createdAt,
    ) {
    }
}
```

### Step 2. Rewrite the interface return types

Change only the return types for `list()` and `item()`.

```php
/** @return array<Article> */
#[DbQuery('article_list')]
public function list(): array;

#[DbQuery('article_item', type: 'row')]
public function item(int $id): Article|null;
```

### Step 3. Rewrite `run.php`

> **About assembling snippets**: Each chapter shows fragments to add or replace in `run.php`; a fragment is not a complete file on its own. When assembling, keep writes such as INSERT before the `list()` / `item()` reads that depend on them. The completed [`docs/tutorial/src/run.php`](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/docs/tutorial/src/run.php) is one possible integrated version, but it is organized for the full demo and will not match your learning file line by line.

Replace the earlier `var_dump($articleQuery->list())` and `var_dump($row)` calls with Entity-aware code.

```php
$articles = $articleQuery->list();
foreach ($articles as $a) {
    printf("[%d] %s by %s\n", $a->id, $a->title, $a->authorName);
}

$first = $articleQuery->item(1);
echo $first?->title, "\n";
```

### Expected Output (chapter 4 / standalone)

```text
[1] Hello by Alice
[2] Second by Bob
Hello
```

### Explanation

- The framework sees return type `Article|null` for one row and docblock `@return array<Article>` for many rows, then builds `Article` objects.
- Because `Article` has a constructor, Ray.MediaQuery chooses `FetchNewInstance` and builds objects through `PDO::FETCH_FUNC`. **SELECT column order is passed directly to constructor argument order.** Chapter 5 covers this in detail.
- Constructor promotion removes the need for getters and setters. `readonly` prevents accidental mutation.
- On PHP 8.4+, `final readonly class Article { ... }` is even shorter.

---

## Chapter 5: Constructor Hydration and SELECT Column Order

### Goal

- Understand what contract made chapter 4 work.
- Learn that Entities with and without constructors use different hydration paths.
- Learn when SQL aliases are needed for snake_case columns and camelCase properties.

### Key Point

When a returned Entity has a constructor, Ray.MediaQuery uses `PDO::FETCH_FUNC` and passes each selected column **from left to right** into the constructor (`FetchNewInstance`).

```sql
-- article_list.sql
SELECT id, title, body, author_name, status, published_at, created_at
FROM article
```

```php
final class Article
{
    public function __construct(
        public readonly int $id,           // SELECT column 1
        public readonly string $title,     // column 2
        public readonly string $body,      // column 3
        public readonly string $authorName,// column 4; DB column is author_name, but order is what matters
        public readonly string $status,
        public readonly string|null $publishedAt,
        public readonly string $createdAt,
    ) {}
}
```

This does **not** work because column names happen to match argument names. It works because the order of `SELECT id, title, body, author_name, ...` matches the order of `__construct(int $id, string $title, string $body, string $authorName, ...)`. If the SQL is changed to `SELECT title, id, ...`, `$id` receives a title string and `$title` receives an integer id, which produces a `TypeError`.

### Try Breaking It Once

Temporarily change `sql/article_list.sql` to the broken order below, run it, then change it back.

```sql
-- Broken example
SELECT title, id, body, author_name, status, published_at, created_at
FROM article;
```

```text
TypeError: MyBlog\Article::__construct(): Argument #1 ($id) must be of type int, string given
```

### Entities Without Constructors

When an Entity has no constructor, Ray.MediaQuery uses the `FetchClass` path (`PDO::FETCH_CLASS`). That path assigns values to properties with the **same names as the columns**. The framework does not convert snake_case to camelCase.

```php
// Entity without constructor
final class ArticleBag
{
    public string $id;
    public string $title;
    public string $author_name;        // same as the column name
    public string|null $published_at;  // same as the column name
    // ...
}
```

If PHP properties should be camelCase, add aliases in SQL.

```sql
SELECT id, title, author_name AS authorName, published_at AS publishedAt
FROM article
```

Modern PHP code often prefers readonly immutable Entities with constructor promotion, which means it usually chooses `FetchNewInstance` and therefore relies on the practical contract **"SELECT column order = constructor argument order."**

### Explanation

- `FetchFactory::factory()` selects the hydration path from the return type and whether the Entity has a constructor. The implementation is in `src/FetchFactory.php`.
- There are five paths: `FetchAssoc` (no Entity), `FetchClass` (Entity without constructor), `FetchNewInstance` (Entity with constructor), `FetchStaticFactory`, and `FetchInjectionFactory` (both selected by `factory:`, covered in chapters 7-8).
- `factory:` also uses `PDO::FETCH_FUNC`, so **SELECT column order is passed to the factory method argument order**.
- The order-based contract is manageable: when SQL changes, review the return Entity at the same time. IDEs, PHPStan, and Psalm will catch many mistakes early.

---

## Chapter 6: DateTime and ToScalar

### Goal

- Pass `DateTimeImmutable` directly and see it converted to a SQL string.
- Convert a value object (`ArticleId`) to a scalar through `ToScalarInterface`.
- Understand automatic injection through `null` defaults.

### Step 1. Write the ArticleId value object

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

### Step 2. Rewrite the interface

In this chapter, change `add()` to return `void` so the focus stays on `DateTimeInterface` conversion. Chapter 10 changes it back to `InsertedRow` to observe the id and final bound values.

Also change `item()` from `int $id` to `ArticleId $id`.

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

### Step 3. Rewrite `run.php`

Update earlier calls to match the new signatures.

- The chapter 3 code `$affected = $articleQuery->add(...); printf("insert affected=%d\n", $affected->count);` no longer works because `add()` returns `void`. Delete the `$affected->count` line, or simply call `$articleQuery->add(...)`.
- The chapter 3 call passed date strings such as `publishedAt: '2026-04-02 10:00:00'`. The new signature expects `DateTimeInterface`, so pass `new DateTimeImmutable('2026-04-02 10:00:00')`.
- The chapter 4 call `$articleQuery->item(1)` becomes `$articleQuery->item(new ArticleId(1))`.

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

### Expected Output (chapter 6 / standalone)

```text
string(19) "2026-04-03 11:00:00"
```

### Explanation

- **DateTime to string**: `ParamConverter` detects `DateTimeInterface` and converts it to a `'Y-m-d H:i:s'` string before passing it to PDO.
- **ToScalarInterface**: the return value from `ArticleId::toScalar()` is bound to `:id`. You can keep type-safe value objects in PHP and reduce them automatically at the SQL boundary.
- **The `null` default trap**: when a parameter has a `null` default, such as `DateTimeInterface|null = null`, omitting the argument triggers Ray.Di injection through `ParamInjector`.
  - **Omitting the argument does not mean "store NULL".** It means "nullable type with a default used by Ray.Di." When omitted, `ParamInjector` resolves the current time.
  - To really store NULL, explicitly pass `publishedAt: null`, or split the use case into a different SQL / method.
  - Chapter 10 uses `InsertedRow::$values` to observe the values after injection and conversion.

> SQLite has no native `DATETIME` type, so values come back as strings. MySQL and PostgreSQL behavior depends on the database type and driver.

---

## Chapter 7: Building Derived Values with a Factory

### Goal

- Return an object that has derived values, such as `excerpt`, `commentCount`, and a `published` flag.
- Use the `factory:` attribute to call a static factory method.

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

### Step 2. Prepare SQL and comments

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

> **Important**: factory method arguments receive values in **SELECT column order** (`PDO::FETCH_FUNC`). The order must match; argument names are not the binding contract.

### Step 3. Static factory

Start with the simplest static factory. Chapter 8 evolves it into a DI factory.

> Later snippets that begin with `namespace MyBlog;` omit the `<?php` and `declare(strict_types=1);` file header. Include them in real files. Also place every `factory:` class in its own PHP file. Defining it inline inside `run.php` can cause `Cannot redeclare class` when Ray.MediaQuery's generated implementation requires the file again.

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
        $excerpt = mb_strlen($body) <= 60 ? $body : mb_substr($body, 0, 60) . '...';

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

### Step 4. Add to the interface

```php
#[DbQuery('article_stats', type: 'row', factory: ArticleStatsFactory::class)]
public function stats(ArticleId $id): ArticleStats;
```

### Step 5. Use it in `run.php`

There are no comments yet, so `commentCount` is `0` until chapter 8.

```php
$stats = $articleQuery->stats(new ArticleId(1));
var_dump($stats);
```

### Expected Output (chapter 7 / standalone)

```text
object(MyBlog\ArticleStats)#... {
  ["id"]=> int(1)
  ["title"]=> string(5) "Hello"
  ["excerpt"]=> string(...) "..."
  ["commentCount"]=> int(0)
  ["published"]=> bool(true)
}
```

### Explanation

- **Static factory vs DI factory**: if the factory method is `static`, Ray.MediaQuery uses `FetchStaticFactory`; if it is an instance method, it uses `FetchInjectionFactory`.
- **Derived-value expressiveness**: values that are not database columns, such as `excerpt` or the `published` flag, can become part of the object's primary responsibility. Entities are not limited to raw row data.

---

## Chapter 8: Injecting Dependencies into a Factory

### Goal

- Inject dependencies into the factory and compute derived values through services. This is the core of the BDR pattern.

### Step 1. Service to inject

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

        return mb_substr($plain, 0, $length) . '...';
    }
}
```

### Step 2. Rewrite the factory as a DI factory

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

### Step 3. Bind `MarkdownExcerpter` in the Module

Add this to the Module's `configure()` method in `run.php`.

```php
$this->bind(MarkdownExcerpter::class);
```

### Step 4. Add comment-related files

To make `stats()` meaningful, add comments. This also revisits Entity hydration through an `array<Comment>` return type.

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

### Step 5. Use `commentQuery` in `run.php`

Add `CommentQueryInterface::class` to `Queries::fromClasses()` and get the instance.

```php
$queries = Queries::fromClasses([
    ArticleQueryInterface::class,
    CommentQueryInterface::class,
]);

/** @var CommentQueryInterface $commentQuery */
$commentQuery = $injector->getInstance(CommentQueryInterface::class);
```

Add comments, then verify `stats()` and `listFor()`.

```php
$commentQuery->add(1, 'Great post!', new DateTimeImmutable('2026-04-01 12:00:00'));
$commentQuery->add(1, 'Thanks!',     new DateTimeImmutable('2026-04-01 13:00:00'));

$stats = $articleQuery->stats(new ArticleId(1));
printf("commentCount=%d, excerpt='%s'\n", $stats->commentCount, $stats->excerpt);

$comments = $commentQuery->listFor(1);
printf("comments=%d, first body='%s' (id=%d)\n", count($comments), $comments[0]->body, $comments[0]->id);
```

### Expected Output (chapter 8 / integrated run.php)

From here onward, article text and counts depend on accumulated previous chapters, so the expected output uses the completed integrated `run.php`.

```text
commentCount=2, excerpt='This is the first post about interface-driven SQL.'
comments=2, first body='Great post!' (id=1)
```

### Explanation

- The factory is instantiated through Ray.Di, so its constructor can receive services freely.
- This is what distinguishes Ray.MediaQuery from a simple query mapper: **domain processing can be applied efficiently at the SQL result boundary**.
- The Business Domain Repository pattern is described in [BDR_PATTERN.md](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN.md).

### BDR focus: why DI is necessary

`age` is not a database column. It requires two inputs: the stored `birth_date` and the current time. The current time must come from outside the factory — injected as `DateTimeInterface`.

Add an `author` table to `mywork/schema.sql`:

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
        public readonly int $age,        // not a column — computed by the factory
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

Add `AuthorQueryInterface::class` to `Queries::fromClasses()`. `AuthorProfileFactory` itself needs no binding — Ray.Di instantiates it and resolves its constructor arguments.

`DateTimeInterface` is already bound to `DateTimeImmutable` inside `MediaQueryModule`, so it resolves to the current time. To keep this sample's `age` reproducible, the tutorial pins the clock in the Module:

```php
$this->bind(DateTimeInterface::class)->toInstance(new DateTimeImmutable('2026-06-06'));
```

Seed an author and call `profile()` in `run.php`:

```php
$pdo->perform('INSERT INTO author (name, birth_date) VALUES (?, ?)', ['Alice', '1990-06-15']);

/** @var AuthorQueryInterface $authorQuery */
$authorQuery = $injector->getInstance(AuthorQueryInterface::class);
$profile = $authorQuery->profile(1);
assert($profile !== null);
printf("name=%s birth_date=%s age=%d\n", $profile->name, $profile->birthDate, $profile->age);
```

### Expected Output (chapter 8 / BDR focus / integrated run.php)

```text
name=Alice birth_date=1990-06-15 age=35
```

> `age` is computed at the query boundary from `birth_date` and the injected `DateTimeInterface $now`. Because the Module pins the clock to `2026-06-06`, `age` is a reproducible `35` (the June 15 birthday has not yet passed that year). Remove the pin and `MediaQueryModule`'s default `DateTimeImmutable` binding resolves to the real current time, so `age` tracks today's date.

The controller and template write `$profile->age` and receive a ready value — no calculation outside the query boundary. This is BDR: the entity arrives complete.

---

## Chapter 9: UPDATE / DELETE and Affected Row Counts

### Goal

- Use the same `AffectedRows` result from chapter 3 for UPDATE and DELETE.

### Step 1. Write SQL

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

### Step 2. Add methods to the interface

```php
use Ray\MediaQuery\Result\AffectedRows;

#[DbQuery('article_update')]
public function update(ArticleId $id, string $title, string $body): AffectedRows;

#[DbQuery('article_delete')]
public function delete(ArticleId $id): AffectedRows;
```

### Step 3. Use them in `run.php`

```php
$updated = $articleQuery->update(new ArticleId(1), 'Hello (edited)', 'updated body');
printf("updated count=%d, isAffected=%s\n", $updated->count, $updated->isAffected() ? 'yes' : 'no');

$deleted = $articleQuery->delete(new ArticleId(2));
printf("deleted count=%d\n", $deleted->count);
```

### Expected Output (chapter 9 / integrated run.php)

```text
updated count=1, isAffected=yes
deleted count=1
```

### Explanation

- `AffectedRows` is a `final class` with a readonly `int $count` property and an `isAffected(): bool` method.
- Declaring `AffectedRows` as the return type is enough for the framework to call `$statement->rowCount()` and construct the result.
- As with chapter 3's INSERT, the framework does not infer intent from the SQL. The return type declares what you want to know.

---

## Chapter 10: Getting the ID and Final Values from INSERT

### Goal

- Use the Ray.MediaQuery 1.1 `InsertedRow` result to receive the auto-generated id and the values that the framework actually passed to the database.
- Learn when `InsertedRow` is a better choice than chapter 3's `AffectedRows`.

### Step 1. Rewrite the interface

Change `add()` from chapter 3's `AffectedRows` and chapter 6's `void` to `InsertedRow`. This is the final `add()` shape used by the tutorial.

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

### Step 2. Rewrite `run.php`

Replace the chapter 6 `$articleQuery->add(...)` call that ignored the return value with code that captures `$inserted`.

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

With this method declaration, omitting `publishedAt` or `createdAt` lets `ParamInjector` inject a `DateTimeInterface` value and lets `ParamConverter` convert it to a SQL string. **Omitting does not mean NULL.** Pass `publishedAt: null` explicitly when you want to store NULL.

```php
$draft = $articleQuery->add(
    title: 'Draft',
    body: 'createdAt is injected',
    authorName: 'Dana',
);

var_dump($draft->values['createdAt']);
```

### Expected Output (chapter 10 / integrated run.php)

In the integrated `run.php`, this `add('Hello', ...)` is the first INSERT, so `id=1`. If your own `run.php` has accumulated earlier INSERTs, the id will be larger.

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
string(19) "2026-04-25 12:34:56" // Example runtime timestamp
```

### Explanation

- **`$inserted->id`**: the result of `pdo->lastInsertId()`. With an `AUTOINCREMENT` column, the new id is returned as a string.
- **`$inserted->values`**: the values after `ParamConverter` / `ParamInjector` resolution. `DateTimeImmutable` has become a string, and `ToScalarInterface` values have been reduced to scalars. This is the only way the caller can observe those final bound values.
- **Choosing the result type**:
  - Need only the row count -> `AffectedRows`
  - Need the id or final bound values -> `InsertedRow`
  - Need nothing -> `void`

---

## Chapter 11: Pagination

### Goal

- Use `#[Pager]` to handle large result sets as `Pages`.
- Keep Article entity hydration with `Pages<Article>`.
- Confirm the Ray.MediaQuery 1.1 fix that combines `#[Pager]` with `factory:` hydration.

### Step 1. SQL

`sql/article_paginated.sql` can be the same as `article_list.sql`.

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

### Step 2. Add to the interface

```php
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

/** @return Pages<Article> */
#[DbQuery('article_paginated')]
#[Pager(perPage: 10)]
public function paginated(): Pages;
```

### Step 3. Add more data in `run.php`

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

### Expected Output (chapter 11 / paginated)

```text
total items=31
page 1 has 10 items, hasNext=yes
Hello, Ray.MediaQuery (edited)
```

### Step 4. Ray.MediaQuery 1.1: Combine Pager and Factory

In 1.1.0, `#[DbQuery(factory: ...)]` is honored even on a query with `#[Pager]`. Rows inside `$page->data` are also built through the factory.

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

Add to `Blog/ArticleQueryInterface.php`:

```php
/** @return Pages<ArticleStats> */
#[DbQuery('article_stats_paginated', factory: ArticleStatsFactory::class)]
#[Pager(perPage: 10)]
public function statsPaginated(): Pages;
```

Verify it in `run.php`.

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

### Expected Output (chapter 11 / statsPaginated)

```text
first stats row=MyBlog\ArticleStats commentCount=2 excerpt='Updated body.'
```

### Explanation

- `count($pages)` returns the **total item count** from the COUNT query, not the number of pages. For the page count call `$pages->getNbPages()`, which reuses the same COUNT.
- `$pages[1]` accesses page 1 and lazily executes SELECT with LIMIT/OFFSET.
- `$page->data` contains a list of `Article` objects because of `@return Pages<Article>`.
- With `#[DbQuery(factory: ArticleStatsFactory::class)]` and `#[Pager]` together, Ray.MediaQuery 1.1+ builds each row in `$page->data` through `ArticleStatsFactory`.
- As in chapter 7, **factory method arguments receive values in SELECT column order**. Pager does not change that contract.
- `$page->hasNext`, `$page->hasPrevious`, and `$page->current` support UI logic. `(string) $page` renders pager HTML.
- See the README for advanced variants such as dynamic page size (`perPage: 'perPage'`).

---

## Chapter 12: Custom PostQueryInterface

### Goal

- Build your own result type like `AffectedRows` or `InsertedRow`.
- Use `PostQueryInterface`, extended in Ray.MediaQuery 1.1 to support SELECT.
- Return a search result with "matched count + executed SQL string."

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

### Step 2. Write the result class

First, add a domain-specific exception instead of throwing a generic `UnexpectedValueException` directly. A specific exception lets callers catch only this intent.

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

### Step 3. Add to the interface

```php
/** @return ArticleSearchResult<Article> */
#[DbQuery('article_search')]
public function search(string $keyword): ArticleSearchResult;
```

The docblock `@return ArticleSearchResult<Article>` is the Entity hydration hint. It tells Ray.MediaQuery to place a list of `Article` objects into `$context->rows` before calling your result class. This is metadata for the framework and static analysis; it is not a PHP runtime generic.

### Step 4. Use it in `run.php`

```php
$result = $articleQuery->search('%Post%');
printf("matched=%d, sql contains 'LIKE'=%s\n", $result->matched, str_contains($result->sql, 'LIKE') ? 'yes' : 'no');
echo "First hit: ", $result->rows[0]->title, "\n";
```

> `$result->sql` (`$context->statement->queryString`) is the SQL after it has been rewritten for execution. Aura.Sql rewrites repeated named placeholders, such as a second `:keyword`, to names like `:keyword__1`, and multi-statement splitting can remove the trailing `;`. Avoid exact string comparison against the SQL file. Use partial checks such as `str_contains($result->sql, 'LIKE')`.

### Expected Output (chapter 12 / integrated run.php)

```text
matched=30, sql contains 'LIKE'=yes
First hit: Post #3
```

### Explanation

- `PostQueryInterface` has one contract: `static fromContext(PostQueryContext): static`.
- `PostQueryContext` contains:
  - `$context->statement` (`PDOStatement`) - `rowCount()`, `queryString`, and so on. `queryString` is the SQL after execution rewriting and may not exactly match the source SQL file.
  - `$context->pdo` (`ExtendedPdoInterface`) - for operations such as `lastInsertId()`.
  - `$context->values` - bound values after `ParamConverter` / `ParamInjector`.
  - `$context->rows` - hydrated results for SELECT paths, or `[]` for DML paths.
- This is a broad extension point for both **DML results** (such as `AffectedRows`, based on `rowCount()`) and **SELECT results** (such as typed wrappers based on `rows`).
- See `src/Result/AffectedRows.php`, `src/Result/InsertedRow.php`, `tests/Fake/Result/Articles.php`, and `tests/Fake/Result/RowCountWithQuery.php` for examples.

---

## Chapter 13: Testing Strategy

### Goal

- Use the "interface as contract" architecture to test business logic.

### Idea

`ArticleQueryInterface` is the contract. In production, Ray.MediaQuery automatically generates an implementation that talks to SQLite or MySQL. In tests, bind a fake implementation and test logic without a database.

### Step 1. Write a fake implementation

For fake methods that are not used in a test, throw a domain-specific exception rather than a generic `\LogicException`.

> This fake implements the final `ArticleQueryInterface`, including `createAndGet()` from the appendix. If you have not read the appendix yet, skip that method for now.

> Place the fake at `mywork/blog/Test/FakeArticleQuery.php` with namespace `MyBlog\Test`, which maps to `mywork/blog/Test/` under PSR-4.

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

### Step 2. Replace the binding in a Module

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

> In the default PHP CLI configuration (`zend.assertions=-1`), `assert()` is removed at compile time and does not verify anything. For a quick manual check, run `php -d zend.assertions=1 mywork/run.php`. In real projects, use PHPUnit assertions instead.

### Explanation

- You can unit-test logic without a database.
- `tests/Fake/Queries/` contains many fake interfaces used by Ray.MediaQuery's own tests and is useful reference material.
- With PHPUnit, install it with `composer require --dev phpunit/phpunit`, extend `PHPUnit\Framework\TestCase`, and use the same Module replacement pattern. PHPUnit assertions such as `assertSame('T', ...)` do not depend on `zend.assertions`.

---

## Appendix: Multi-statement DML + SELECT

The main Ray.MediaQuery features have now been covered. One more 1.1 feature is useful: `PostQueryInterface` can receive SELECT results, so you can express "INSERT, then SELECT the created row" as one method contract.

### Goal

- Put multiple statements (INSERT -> SELECT) in one SQL file.
- Use 1.1's `PostQueryContext::$rows` to receive the final SELECT as hydrated Entities inside a custom `PostQueryInterface`.

### Step 1. SQL

A use case such as "create an article and return the created row" is often implemented by hand as INSERT, last insert id, SELECT, hydrate. Ray.MediaQuery can declare the whole interaction with SQL and a return type.

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

> `last_insert_rowid()` is SQLite-specific. MySQL uses `LAST_INSERT_ID()`, and PostgreSQL or SQLite 3.35+ can also use `INSERT ... RETURNING`. Ray.MediaQuery splits multiple statements by `;`, so the final SELECT should also end with `;`.

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

### Expected Output (appendix / integrated run.php)

```text
created article id=33 title='Created and fetched' status=draft
```

### Explanation

- Statements in a SQL file are split by `;`. `createAndGet()` executes INSERT -> SELECT as **two statements in one method call**.
- `PostQueryContext::$rows` contains the hydrated result when the final statement is SELECT. If the final statement is DML, it is `[]`.
- `CreatedArticle::fromContext()` reads `$context->rows[0]` as an `Article`. The docblock `@return CreatedArticle<Article>` on `ArticleQueryInterface::createAndGet()` is the hydration hint.
- This pattern works for "INSERT then immediately SELECT" and "UPDATE then return the latest row" style interactions.

The important point is that `createAndGet()` is not a convenience method on an `ArticleRepository`; it is a typed Query contract for "create an article and return the created result."

---

## Conclusion: Query-first vs Repository Pattern

Throughout this tutorial, queries were expressed with interface + attribute + SQL + return type, without writing repository implementation classes.

This is not just a shorter Repository Pattern. A Repository abstracts a persisted object collection. Ray.MediaQuery abstracts an executable Query contract.

| Viewpoint | Repository Pattern | Ray.MediaQuery |
|-----------|--------------------|----------------|
| Center | Entity / Aggregate | Query / UseCase |
| Main use | Write Model; save and restore Aggregates | Read Model, Projection, CQRS Query side |
| Implementation | Handwritten Repository class | Interface + Attribute + SQL |
| Result processing | Procedures inside Repository implementation | `factory:` / `PostQueryInterface` |
| SQL | Easy to bury inside implementation | Explicit SQL files |
| Replacement | Replace Repository interface with fake / mock | Replace Query interface with fake / mock |

Repositories are still useful. On the write side, where you restore, mutate, and save Aggregates, they remain a valid abstraction.

On the read side, you often want a projection shaped for a specific use case. If an Entity-centered Repository expands to serve dashboards, search, admin screens, and analytics, too many entry points accumulate in one place.

Ray.MediaQuery splits the read side query-first. Instead of adding methods to `UserRepository`, define contracts such as `UserDashboardQuery`, `ArticleSearchQuery`, and `MonthlyStatsQuery` for the interactions themselves.

> **Simplification in this tutorial**: to keep the walkthrough compact, `list`, `item`, `add`, `update`, `delete`, `paginated`, `statsPaginated`, `stats`, `search`, and `createAndGet` are gathered into one `ArticleQueryInterface`. In a real project, split by use case, such as `ArticleSearchQueryInterface`, `ArticleStatsQueryInterface`, and `ArticleCommandInterface`. Fakes become smaller and responsibilities clearer.

The appendix's DML + SELECT example pushes this query-first idea further. Use Repositories for write-side Aggregate persistence. Use query-first contracts for read-side projection retrieval and typed results after DML. Ray.MediaQuery exists to make the latter explicit with interfaces and SQL.

---

## You Finished the Walkthrough

You have now experienced the main Ray.MediaQuery features.

This hands-on tutorial focuses on understanding application Query contracts built from interface + SQL + return type. The following advanced features are not implemented here; see the Manual for details.

- `#[Input]` Object Flattening - flatten input DTOs into SQL parameters.
- Direct `SqlQueryInterface` execution - execute SQL through a lower-level API instead of an interface.
- `#[Pager(perPage: 'perPage')]` - change page size dynamically through a method argument.
- `MediaQuerySqlModule` - shorter module configuration that discovers Query interfaces from a directory.
- `SqlTemplate` / `MediaQuerySqlTemplateModule` - advanced SQL execution template customization.

### Next Reading

- [BDR Pattern Cookbook](https://ray-di.github.io/Ray.MediaQuery/tutorial/bdr-patterns/) - per-row enrichment (`factory:`) vs. whole-result-set shaping (`PostQueryInterface`): badges, enums, JOIN grouping, sorting, SPL iterators, Null Object
- [BDR Pattern Guide](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN.md) - factory pattern and domain object design
- [Manual 日本語版](https://ray-di.github.io/Ray.MediaQuery/reference/) - advanced feature reference, including `#[Input]` Object Flattening and direct `SqlQueryInterface` execution
- [llms-full.txt](../llms-full.txt) - compact reference for AI agents
- [`tests/Fake/`](https://github.com/ray-di/Ray.MediaQuery/tree/1.x/tests/Fake) - real test examples

### Community

- [Issues](https://github.com/ray-di/Ray.MediaQuery/issues)
- [BEAR.Sunday](https://bearsunday.github.io/) - application framework that includes Ray.MediaQuery
