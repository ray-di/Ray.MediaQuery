---
layout: default
title: Ray.MediaQuery Feature Reference
description: Detailed feature reference for Ray.MediaQuery result mapping, factories, parameter handling, pagination, and direct SQL execution.
lang: en
permalink: /reference/
---

# Ray.MediaQuery Feature Reference

Detailed reference for Ray.MediaQuery features. For a guided introduction, start with the [hands-on tutorial](https://ray-di.github.io/Ray.MediaQuery/tutorial/).

## Features

### Result Mapping & Entity Hydration

Ray.MediaQuery automatically hydrates query results based on your return type declarations:

**Single Entity:**
```php
interface UserRepository
{
    #[DbQuery('user_find')]
    public function find(string $id): ?User;  // Returns User or null
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

**Entity Array:**
```php
interface UserRepository
{
    #[DbQuery('user_list')]
    /** @return array<User> */
    public function findAll(): array;  // Returns User[]
}
```

**Raw Array (single row):**
```php
interface UserRepository
{
    #[DbQuery('user_stats', type: 'row')]
    public function getStats(string $id): ?array;  // ['total' => 10, 'active' => 5], or null when no row matches
}
```

> With `type: 'row'` the query returns a single row, and `null` when no row matches. Declare the return type as nullable (`?array`, or `?Entity` for a hydrated row) to avoid a `TypeError` on empty results.

**Raw Array (multiple rows):**
```php
interface UserRepository
{
    #[DbQuery('user_list')]
    public function listRaw(): array;  // [['id' => '1', ...], ['id' => '2', ...]]
}
```

**DML Result types — `AffectedRows` / `InsertedRow`:**

Declare a result type that implements `PostQueryInterface` to receive post-execution information. The framework ships two:

- `AffectedRows` — row count for `UPDATE` / `DELETE`.
- `InsertedRow` — the resolved parameter values plus the auto-increment id for `INSERT`.

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
$inserted->values;  // array<string, mixed> — parameters as bound to the driver (UUIDs, timestamps, DateTime→string, ToScalar reductions all resolved)
$inserted->id;      // ?string — auto-increment id, null if the driver reports none

$deleted = $todoRepo->delete('1');
$deleted->count;       // int — rows deleted
$deleted->isAffected();  // bool — true when count > 0
```

`InsertedRow::$values` is the result of Ray.MediaQuery's parameter resolution — injected defaults (UUIDs, timestamps), `DateTime` converted to SQL strings, and `ToScalarInterface` value objects reduced to scalars. Those are the values that actually went to the database and are not otherwise observable by the caller.

The return type **is** the intent declaration — the framework does not sniff the SQL. Pick `InsertedRow` when you need the id or the resolved values, `AffectedRows` otherwise. Existing `void` return types keep working unchanged.

When a SQL file contains multiple statements (separated by `;`), the result reflects the **last executed statement only**.

**Custom result types:**

Any class implementing `Ray\MediaQuery\Result\PostQueryInterface` can be declared as a return type. The interface defines a single static factory that builds the result from a `PostQueryContext` carrying the executed statement, the connection, and the resolved parameter values:

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

Declare it on any `#[DbQuery]` method and the interceptor dispatches to the class's own factory.

**SELECT collections — typed row wrappers:**

`PostQueryInterface` also covers SELECT. The framework pre-hydrates the result set into `PostQueryContext::$rows` (entity instances when a `factory:` attribute or `@return Wrapper<Entity>` docblock resolves an entity, associative arrays otherwise). Your wrapper class composes those rows — it never touches raw `PDOStatement` or DI:

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

Callers get `$articles->published()->totalWordCount()` — domain logic about the result set lives on the type, not scattered across services. `IteratorAggregate` / `Countable` give the wrapper standard "feels like an array" ergonomics. To compose a richer base, wrap a Laravel / Illuminate / Doctrine `Collection` via a property the same way.

`$rows` shape is determined by what the framework hands the wrapper:

- `@return Articles<Article>` docblock or `factory:` attribute → entity instances.
- Neither declared → associative arrays.
- DML statement → `[]` (no fetch happens).

`$rows === []` therefore means either "DML, didn't fetch" or "SELECT, no matches" — pick a result class scoped to one or the other rather than trying to handle both shapes.

**Generic base for reuse across repositories:**

Lift the entity out as a type variable when several repositories want the same shape with different entities. Psalm and PHPStan propagate the parameter through `foreach`, `$rows[N]`, and `iterator_to_array(...)`:

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

`@extends TypedRows<Article>` carries `Article` through to every site that inspects the rows — `$articles->rows[0]->title`, `foreach ($articles as $a) { $a->wordCount; }`, and any derived method on the base. The framework still hands `$context->rows` as `array<mixed>`; the narrow happens at the `@var list<T>` line in `fromContext()`, and from that point on the static analyser honours the parameter. Runtime is identical to the single-type wrapper above — PHP has no native generics, so this is a static-analysis claim, not a runtime check.

**Constructor Property Promotion (Recommended):**

Use constructor property promotion for type-safe, immutable entities:

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
// Ray.MediaQuery handles snake_case → camelCase conversion automatically
```

For PHP 8.4+, use readonly classes:

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

### Factory Pattern for Complex Objects

Use factories when entities need computed properties or injected services:

**Keep domain knowledge out of controllers:**

The database may store only `birth_date`, while the object exposed to the application has `age`. The age calculation is domain knowledge — "full years as of today", timezone policy, and leap-day handling — and should not be repeated in controllers or templates.

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
        public readonly int $age,  // not a database column
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
    public function profile(string $id): ?UserProfile;  // null when no row matches
}
```

The controller receives a `UserProfile` that already speaks the domain language:

```php
$profile = $userProfileQuery->profile($id);

return [
    'name' => $profile->name,
    'age' => $profile->age,
];
```

No controller needs to know how `birth_date` becomes `age`; the transformation stays at the SQL/domain boundary.

**Basic Factory:**
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
            tax: $amount * 0.1,      // Computed
            total: $amount * 1.1,    // Computed
        );
    }
}
```

**Factory with Dependency Injection:**
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

**Polymorphic Entities:**
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

> **Architecture Pattern**: Factories enable the [**BDR Pattern**](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN.md) - combining efficient SQL with rich domain objects through dependency injection.

### Smart Parameter Handling

**DateTime Automatic Conversion:**
```php
interface TaskRepository
{
    #[DbQuery('task_add')]
    public function add(string $title, DateTimeInterface $createdAt = null): void;
}

// SQL: INSERT INTO tasks (title, created_at) VALUES (:title, :createdAt)
// DateTime converted to: '2024-01-15 10:30:00'
// null injects current time automatically
```

**Value Objects:**
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

// UserId automatically converted via toScalar()
```

**Parameter Injection:**
```php
interface TodoRepository
{
    #[DbQuery('todo_add')]
    public function add(string $title, Uuid $id = null): void;
}

// null triggers DI: Uuid is generated and injected automatically
```

### Input Object Flattening

Structure your input while keeping SQL simple with `Ray.InputQuery`.

> **Note**: This feature requires the `ray/input-query` package, which is already included as a dependency.

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
        #[Input] public readonly ?DateTimeInterface $dueDate
    ) {}
}

interface TodoRepository
{
    #[DbQuery('todo_create')]
    public function create(TodoInput $input): void;
}

// Input flattened automatically:
// :title, :givenName, :familyName, :email, :dueDate
```

### Pagination

Enable lazy-loaded pagination with the `#[Pager]` attribute:

**Basic Pagination:**
```php
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface ProductRepository
{
    #[DbQuery('product_list'), Pager(perPage: 20, template: '/{?page}')]
    public function getProducts(): Pages;
}

$pages = $productRepo->getProducts();
$count = count($pages);  // Executes COUNT query
$page = $pages[1];       // Executes SELECT with LIMIT/OFFSET

// Page object properties:
// $page->data          // Items for this page
// $page->current       // Current page number
// $page->total         // Total pages
// $page->hasNext       // Has next page?
// $page->hasPrevious   // Has previous page?
// (string) $page       // Pager HTML
```

**Dynamic Page Size:**
```php
interface ProductRepository
{
    #[DbQuery('product_list'), Pager(perPage: 'perPage', template: '/{?page}')]
    public function getProducts(int $perPage): Pages;
}
```

**With Entity Hydration:**
```php
interface ProductRepository
{
    #[DbQuery('product_list'), Pager(perPage: 20)]
    /** @return Pages<Product> */
    public function getProducts(): Pages;
}

// Each page's data is hydrated to Product entities
```

### Direct SQL Execution

For advanced use cases, inject `SqlQueryInterface` directly:

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

**Available Methods:**
- `getRow($queryId, $params)` - Single row
- `getRowList($queryId, $params)` - Multiple rows
- `exec($queryId, $params)` - Execute without result
- `execPostQuery($queryId, $params, $postQueryClass, FetchInterface|null $fetch = null)` - Execute a SQL statement (SELECT or DML) and build a typed result via a `PostQueryInterface` class (e.g. `AffectedRows`, `InsertedRow`, a typed collection wrapper, or any custom class). When `$fetch` is supplied, SELECT rows arrive on the context already hydrated to that strategy's shape.
- `getCount($queryId, $params)` - Total row count (for pagination)
- `getStatement()` - Get PDO statement
- `getPages()` - Get paginated results
