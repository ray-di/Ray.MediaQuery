---
layout: default
title: Ray.MediaQuery マニュアル
description: Ray.MediaQuery のインストール、モジュール設定、SQL ファイル規約、戻り値マッピング、ファクトリ、パラメータ処理、ページネーション、直接 SQL 実行のユーザーマニュアル。
lang: ja
permalink: /reference/
---

# Ray.MediaQuery マニュアル

Ray.MediaQuery を使うための完全ガイドです。パッケージのインストール、モジュール配線、SQL ファイル規約を押さえた上で、各機能の詳細を確認できます。順を追って体験したい場合は、[ハンズオンチュートリアル](https://ray-di.github.io/Ray.MediaQuery/tutorial/) から始めてください。

このマニュアルで扱う内容:

- [インストール](#インストール)
- [セットアップ](#セットアップ) — DI モジュールの配線と query instance の取得
- [SQL ファイル](#sql-ファイル) — 配置場所、命名、placeholder 規約
- [設定](#設定) — 接続、module の選択、高度な hook
- [機能](#機能) — 結果マッピング、factory、parameter、pagination、直接 SQL 実行

## インストール

```bash
composer require ray/media-query
```

要件:

- **PHP 8.2+**。
- 利用するデータベースの PDO driver (`pdo_sqlite`, `pdo_mysql` など)。
- Ray.MediaQuery は [Ray.Di](https://ray-di.github.io/) (dependency injection) と [Ray.AuraSqlModule](https://github.com/ray-di/Ray.AuraSqlModule) (PDO connection) を基盤にします。どちらも依存として install されます。

## セットアップ

Ray.MediaQuery は query interface の実装を runtime に生成します。そのため setup で必要なのは 2 つです。MediaQuery module を install して interface と SQL directory を対応づけ、`AuraSqlModule` を install して database connection を供給します。その後、injector から interface を取得します。

### Auto-discovery: `MediaQuerySqlModule` (推奨)

Query interface の directory と SQL file の directory を module に渡します。`interfaceDir` 配下にある interface は自動で bind されます。

```php
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\MediaQuerySqlModule;

final class AppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new MediaQuerySqlModule(
            interfaceDir: __DIR__ . '/Query',  // #[DbQuery] interface の directory
            sqlDir: __DIR__ . '/sql',          // .sql file の directory
        ));
        $this->install(new AuraSqlModule('sqlite::memory:'));  // PDO connection (DSN)
    }
}

$injector = new Injector(new AppModule());
$userQuery = $injector->getInstance(UserQueryInterface::class);  // 生成された実装
$user = $userQuery->item('user-123');
```

### 明示リスト: `MediaQueryModule`

Directory scan ではなく interface を明示したい場合は、`Queries` list を作り、SQL directory 用の `DbQueryConfig` と一緒に渡します。

```php
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;

protected function configure(): void
{
    $queries = Queries::fromClasses([
        UserQueryInterface::class,
        OrderQueryInterface::class,
    ]);
    $this->install(new MediaQueryModule($queries, [new DbQueryConfig(__DIR__ . '/sql')]));
    $this->install(new AuraSqlModule('sqlite::memory:'));
}
```

どちらの module も同じ結果を作ります。`MediaQuerySqlModule` は directory-based の shortcut、`MediaQueryModule` は明示的な構成です。Directory から `Queries` list を作りたい場合は `Queries::fromDir($dir)` も使えます。

## SQL ファイル

- Query ごとに `{queryId}.sql` という名前で `sqlDir` に保存します。`#[DbQuery('user_item')]` は `sqlDir/user_item.sql` に対応します。
- Placeholder は **named** で、同じ名前の method argument に bind されます。たとえば `:userId` は `string $userId` に対応します。名前で bind されるため、引数順は問いません。
- 1 ファイルに複数 statement を書けます。Statement は `;` で区切られ、順に実行されます。結果は**最後の statement** を反映します。詳細は [結果マッピング](#結果マッピングと-entity-hydration) を参照してください。

```sql
-- sql/user_item.sql
SELECT id, name FROM users WHERE id = :id;
```

```php
interface UserQueryInterface
{
    #[DbQuery('user_item', type: 'row')]
    public function item(string $id): User|null;
}
```

## 設定

- **Database connection** — `AuraSqlModule` が供給します。`'mysql:host=localhost;dbname=app'`, `'pgsql:host=...;dbname=...'`, `'sqlite::memory:'` など任意の PDO DSN を渡せます。Connection pooling、primary/replica、connection option は [Ray.AuraSqlModule](https://github.com/ray-di/Ray.AuraSqlModule) を参照してください。
- **Module choice** — `MediaQuerySqlModule` (directory scan) と `MediaQueryModule` (明示的な `Queries` + `DbQueryConfig`) を選べます。詳細は [セットアップ](#セットアップ) を参照してください。
- **Advanced hooks** — `MediaQuerySqlTemplateModule` / `SqlTemplate` で SQL execution template を差し替えられます。`MediaQueryLoggerInterface` は query logging の拡張点です。通常の application は上記 2 つの module で足ります。

## 機能

### 結果マッピングと Entity Hydration

Ray.MediaQuery は、メソッドの戻り値型宣言に基づいてクエリ結果を自動的に hydrate します。

**単一 Entity:**

```php
interface UserRepository
{
    #[DbQuery('user_find')]
    public function find(string $id): User|null;  // User または null を返す
}

class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email
    ) {}
}
```

**Entity 配列:**

```php
interface UserRepository
{
    #[DbQuery('user_list')]
    /** @return array<User> */
    public function findAll(): array;  // User[] を返す
}
```

**生配列 (単一行):**

```php
interface UserRepository
{
    #[DbQuery('user_stats', type: 'row')]
    public function getStats(string $id): array;  // ['total' => 10, 'active' => 5]
}
```

**生配列 (複数行):**

```php
interface UserRepository
{
    #[DbQuery('user_list')]
    public function listRaw(): array;  // [['id' => '1', ...], ['id' => '2', ...]]
}
```

**DML 結果型: `AffectedRows` / `InsertedRow`**

`PostQueryInterface` を実装する結果型を戻り値として宣言すると、実行後の情報を受け取れます。フレームワークには以下の 2 つが同梱されています。

- `AffectedRows` — `UPDATE` / `DELETE` の影響行数。
- `InsertedRow` — 解決済みパラメータ値と、`INSERT` 後の auto-increment id。

```php
use Ray\MediaQuery\Result\AffectedRows;
use Ray\MediaQuery\Result\InsertedRow;

interface TodoRepository
{
    #[DbQuery('todo_add')]
    public function add(string $title): InsertedRow;

    #[DbQuery('todo_update')]
    public function update(string $id, string $title): AffectedRows;

    #[DbQuery('todo_delete')]
    public function delete(string $id): AffectedRows;
}

$inserted = $todoRepo->add('Write docs');
$inserted->values;  // array<string, mixed> — ドライバに bind された実際の値 (UUID、timestamp、DateTime→string、ToScalar の縮約後)
$inserted->id;      // string|null — auto-increment id。ドライバが返さない場合は null

$deleted = $todoRepo->delete('1');
$deleted->count;       // int — 削除行数
$deleted->isAffected();  // bool — count > 0 なら true
```

`InsertedRow::$values` は Ray.MediaQuery のパラメータ解決結果です。注入されたデフォルト値 (UUID、timestamp)、SQL 文字列へ変換された `DateTime`、スカラーに縮約された `ToScalarInterface` 値オブジェクトなど、実際にデータベースへ渡された値が入ります。呼び出し側からは通常観測できない値です。

戻り値型そのものが意図の宣言です。フレームワークは SQL を推測して判定しません。id や解決済み値が必要なら `InsertedRow`、影響行数だけでよいなら `AffectedRows` を選びます。既存の `void` 戻り値型はそのまま動作します。

SQL ファイルに複数 statement (`;` 区切り) が含まれる場合、結果は**最後に実行された statement** だけを反映します。

**カスタム結果型:**

`Ray\MediaQuery\Result\PostQueryInterface` を実装する任意のクラスを戻り値型として宣言できます。このインターフェイスは、実行済み statement、接続、解決済みパラメータ値を持つ `PostQueryContext` から結果を組み立てる static factory を 1 つ定義します。

```php
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class RowCountWithQuery implements PostQueryInterface
{
    public function __construct(
        public readonly int $count,
        public readonly string $queryString,
    ) {}

    public static function fromContext(PostQueryContext $context): static
    {
        return new static($context->statement->rowCount(), $context->statement->queryString);
    }
}
```

`#[DbQuery]` メソッドの戻り値にこのクラスを宣言すると、インターセプタはそのクラス自身の factory に処理を委譲します。

**SELECT コレクション: 型付き row ラッパー**

`PostQueryInterface` は SELECT にも対応します。フレームワークは結果セットを `PostQueryContext::$rows` に事前 hydrate します。`factory:` 属性や `@return Wrapper<Entity>` docblock から Entity が解決できる場合は Entity インスタンス、それ以外は連想配列です。ラッパークラスはそれらの row を合成するだけで、raw `PDOStatement` や DI を直接扱いません。

```php
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

/** @implements IteratorAggregate<int, Article> */
final class Articles implements PostQueryInterface, IteratorAggregate, Countable
{
    /** @param list<Article> $rows */
    public function __construct(public readonly array $rows) {}

    public static function fromContext(PostQueryContext $context): static
    {
        /** @var list<Article> $rows */
        $rows = $context->rows;

        return new static($rows);
    }

    /** Domain predicates / aggregations — the reason to wrap. */
    public function published(): self
    {
        return new self(array_values(array_filter(
            $this->rows,
            static fn (Article $a): bool => $a->isPublished(),
        )));
    }

    public function totalWordCount(): int
    {
        return array_sum(array_map(
            static fn (Article $a): int => $a->wordCount,
            $this->rows,
        ));
    }

    /** @return ArrayIterator<int, Article> */
    public function getIterator(): ArrayIterator { return new ArrayIterator($this->rows); }
    public function count(): int { return count($this->rows); }
}

interface ArticleRepository
{
    /** @return Articles<Article> */
    #[DbQuery('article_list')]
    public function list(): Articles;
}
```

呼び出し側は `$articles->published()->totalWordCount()` のように扱えます。結果セットに関するドメインロジックはサービス層に散らばらず、型の上に置かれます。`IteratorAggregate` / `Countable` を実装すれば、標準的な「配列らしい」操作感も得られます。よりリッチな基盤が必要なら、Laravel / Illuminate / Doctrine の `Collection` をプロパティとして包むのも同じ考え方です。

`$rows` の形はフレームワークがラッパーに渡すものによって決まります。

- `@return Articles<Article>` docblock または `factory:` 属性 → Entity インスタンス。
- どちらもない → 連想配列。
- DML statement → `[]`。fetch は行われません。

したがって `$rows === []` は「DML なので fetch していない」場合と「SELECT だが一致行がない」場合の両方を表せます。両方を 1 つの結果クラスで無理に扱うより、用途ごとに結果クラスを分ける方が明確です。

**再利用できる generic base:**

複数の repository で同じ形を使い、Entity だけが違う場合は、Entity を型変数として抜き出します。Psalm と PHPStan は `foreach`、`$rows[N]`、`iterator_to_array(...)` まで型パラメータを伝播します。

```php
/**
 * @template T
 * @implements IteratorAggregate<int, T>
 */
abstract class TypedRows implements PostQueryInterface, IteratorAggregate, Countable
{
    /** @param list<T> $rows */
    public function __construct(public readonly array $rows) {}

    public static function fromContext(PostQueryContext $context): static
    {
        /** @var list<T> $rows */
        $rows = $context->rows;

        return new static($rows);
    }

    /** @return ArrayIterator<int, T> */
    public function getIterator(): ArrayIterator { return new ArrayIterator($this->rows); }
    public function count(): int { return count($this->rows); }
    public function isEmpty(): bool { return $this->rows === []; }
}

/** @extends TypedRows<Article> */
final class Articles extends TypedRows
{
    public function published(): self { /* domain operations on Article rows */ }
    public function totalWordCount(): int { /* ... */ }
}

/** @extends TypedRows<User> */
final class Users extends TypedRows {}
```

`@extends TypedRows<Article>` により、`Article` は row を調べるすべての場所へ伝わります。`$articles->rows[0]->title`、`foreach ($articles as $a) { $a->wordCount; }`、base class 上の派生メソッドも同様です。フレームワークが `$context->rows` として渡す型は実行時には `array<mixed>` のままです。`fromContext()` 内の `@var list<T>` で narrow し、その後は静的解析器が型パラメータを尊重します。PHP にはネイティブ generic がないため、これは実行時チェックではなく静的解析上の主張です。

**Constructor Property Promotion (推奨):**

型安全で immutable な Entity には constructor property promotion を使います。

```php
final class Invoice
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $userName,      // camelCase property
        public readonly string $emailAddress,  // camelCase property
    ) {}
}

// SQL: SELECT id, title, user_name, email_address FROM invoices
// Hydration is positional, not name-based: each SELECT column is passed to the
// constructor argument in the same order (PDO::FETCH_FUNC), so the snake_case
// column names need not match the camelCase property names — the column order is
// the contract. (Constructor-less entities use PDO::FETCH_CLASS, which matches by
// property name and needs a SQL alias instead.)
```

PHP 8.4 以降では readonly class も使えます。

```php
final readonly class Invoice
{
    public function __construct(
        public string $id,
        public string $title,
        public string $userName,
        public string $emailAddress,
    ) {}
}
```

### 複雑なオブジェクトのための Factory Pattern

計算済みプロパティや注入サービスが必要な Entity には factory を使います。

**ドメイン知識を controller から出す:**

データベースには `birth_date` しか保存されていない一方で、アプリケーションに公開するオブジェクトには `age` が必要な場合があります。年齢計算は「今日時点の満年齢」、タイムゾーン方針、うるう日処理を含むドメイン知識であり、controller や template に繰り返し書くべきではありません。

```sql
-- sql/user_profile.sql
SELECT
    id,
    name,
    birth_date
FROM users
WHERE id = :id;
```

```php
final class UserProfile
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $age,  // database column ではない
    ) {}
}

final class AgeCalculator
{
    public function fromBirthDate(\DateTimeImmutable $birthDate): int
    {
        return $birthDate->diff(new \DateTimeImmutable('today'))->y;
    }
}

final class UserProfileFactory
{
    public function __construct(
        private AgeCalculator $ageCalculator,
    ) {}

    public function factory(string $id, string $name, string $birthDate): UserProfile
    {
        return new UserProfile(
            id: $id,
            name: $name,
            age: $this->ageCalculator->fromBirthDate(new \DateTimeImmutable($birthDate)),
        );
    }
}

interface UserProfileQuery
{
    #[DbQuery('user_profile', type: 'row', factory: UserProfileFactory::class)]
    public function profile(string $id): UserProfile;
}
```

controller は、すでにドメイン語彙で語れる `UserProfile` を受け取ります。

```php
$profile = $userProfileQuery->profile($id);

return [
    'name' => $profile->name,
    'age' => $profile->age,
];
```

`birth_date` から `age` を作る方法を controller が知る必要はありません。変換は SQL / domain 境界に閉じ込められます。

**基本的な factory:**

```php
interface OrderRepository
{
    #[DbQuery('order_detail', factory: OrderFactory::class)]
    public function getOrder(string $id): Order;
}

class OrderFactory
{
    public function factory(string $id, float $amount): Order
    {
        return new Order(
            id: $id,
            amount: $amount,
            tax: $amount * 0.1,      // 計算値
            total: $amount * 1.1,    // 計算値
        );
    }
}
```

**依存注入を使う factory:**

```php
class OrderFactory
{
    public function __construct(
        private TaxCalculator $taxCalc,       // Injected
        private ShippingService $shipping,    // Injected
    ) {}

    public function factory(string $id, float $amount, string $region): Order
    {
        return new Order(
            id: $id,
            amount: $amount,
            tax: $this->taxCalc->calculate($amount, $region),
            shipping: $this->shipping->calculate($region),
        );
    }
}
```

**ポリモーフィック Entity:**

```php
class UserFactory
{
    public function factory(string $id, string $type, string $email): UserInterface
    {
        return match ($type) {
            'free' => new FreeUser($id, $email, maxStorage: 100),
            'premium' => new PremiumUser($id, $email, maxStorage: 1000),
        };
    }
}
```

> **Architecture Pattern**: factory は [**BDR Pattern**]({{ '/bdr-pattern/ja/' | relative_url }}) を実現します。効率的な SQL と、依存注入を通じて組み立てられるリッチなドメインオブジェクトを組み合わせる設計です。

### 賢いパラメータ処理

**DateTime の自動変換:**

```php
interface TaskRepository
{
    #[DbQuery('task_add')]
    public function add(string $title, DateTimeInterface|null $createdAt = null): void;
}

// SQL: INSERT INTO tasks (title, created_at) VALUES (:title, :createdAt)
// DateTime は '2024-01-15 10:30:00' に変換される
// null は現在時刻の自動注入を起動する
```

**値オブジェクト:**

```php
class UserId implements ToScalarInterface
{
    public function __construct(private int $value) {}

    public function toScalar(): int
    {
        return $this->value;
    }
}

interface MemoRepository
{
    #[DbQuery('memo_add')]
    public function add(string $memo, UserId $userId): void;
}

// UserId は toScalar() を通じて自動変換される
```

**パラメータ注入:**

```php
interface TodoRepository
{
    #[DbQuery('todo_add')]
    public function add(string $title, Uuid|null $id = null): void;
}

// null により DI が起動し、Uuid が生成・注入される
```

### Input Object Flattening

`Ray.InputQuery` を使うと、入力を構造化しながら SQL は単純に保てます。

> **Note**: この機能には `ray/input-query` package が必要です。Ray.MediaQuery には依存として含まれています。

```php
use Ray\InputQuery\Attribute\Input;

class UserInput
{
    public function __construct(
        #[Input] public readonly string $givenName,
        #[Input] public readonly string $familyName,
        #[Input] public readonly string $email
    ) {}
}

class TodoInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly UserInput $assignee,  // Nested
        #[Input] public readonly DateTimeInterface|null $dueDate
    ) {}
}

interface TodoRepository
{
    #[DbQuery('todo_create')]
    public function create(TodoInput $input): void;
}

// Input は自動的に flatten される:
// :title, :givenName, :familyName, :email, :dueDate
```

### ページネーション

`#[Pager]` 属性で遅延ロードされるページネーションを有効にします。

**基本的なページネーション:**

```php
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface ProductRepository
{
    #[DbQuery('product_list')]
    #[Pager(perPage: 20, template: '/{?page}')]
    public function getProducts(): Pages;
}

$pages = $productRepo->getProducts();
$count = count($pages);  // COUNT query を実行
$page = $pages[1];       // LIMIT/OFFSET 付き SELECT を実行

// Page object properties:
// $page->data          // このページの item
// $page->current       // 現在ページ番号
// $page->total         // 全 item 数 (count($pages) と同じ)
// $page->hasNext       // 次ページがあるか
// $page->hasPrevious   // 前ページがあるか
// (string) $page       // Pager HTML
```

**動的ページサイズ:**

```php
interface ProductRepository
{
    #[DbQuery('product_list')]
    #[Pager(perPage: 'perPage', template: '/{?page}')]
    public function getProducts(int $perPage): Pages;
}
```

**Entity Hydration との併用:**

```php
interface ProductRepository
{
    #[DbQuery('product_list')]
    #[Pager(perPage: 20)]
    /** @return Pages<Product> */
    public function getProducts(): Pages;
}

// 各 page の data は Product entity に hydrate される
```

### 直接 SQL 実行

高度な用途では `SqlQueryInterface` を直接注入できます。

```php
use Ray\MediaQuery\SqlQueryInterface;

class CustomRepository
{
    public function __construct(
        private SqlQueryInterface $sqlQuery
    ) {}

    public function complexQuery(array $params): array
    {
        return $this->sqlQuery->getRowList('complex_query', $params);
    }
}
```

**利用可能なメソッド:**

- `getRow($queryId, $params)` — 単一行を取得。
- `getRowList($queryId, $params)` — 複数行を取得。
- `exec($queryId, $params)` — 結果を受け取らずに実行。
- `execPostQuery($queryId, $params, $postQueryClass, FetchInterface|null $fetch = null)` — SQL statement (SELECT または DML) を実行し、`PostQueryInterface` class を通じて型付き結果を構築します。`AffectedRows`、`InsertedRow`、型付き collection wrapper、任意の custom class などに使えます。`$fetch` を指定した場合、SELECT row はその strategy の形に hydrate された状態で context に渡ります。
- `getCount($queryId, $params)` — 総行数を取得 (ページネーション用)。
- `getStatement()` — PDO statement を取得。
- `getPages()` — ページング結果を取得。
