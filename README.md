# Ray.MediaQuery

[![codecov](https://codecov.io/gh/ray-di/Ray.MediaQuery/branch/1.x/graph/badge.svg?token=QBOPCUPJQV)](https://codecov.io/gh/ray-di/Ray.MediaQuery)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.MediaQuery/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.MediaQuery)
[![Continuous Integration](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml)

## Interface-Driven SQL for PHP

**Ray.MediaQuery lets SQL be SQL and Objects be Objects.**

Traditional ORMs try to hide SQL behind object abstractions. Ray.MediaQuery takes a different approach:

```php
// 1. Define your interface
interface UserRepository
{
    #[DbQuery('user_by_id')]
    public function find(string $id): User;
}

// 2. Write your SQL
-- user_by_id.sql
SELECT * FROM users WHERE id = :id

// 3. That's it. No implementation needed.
// Ray.MediaQuery generates everything else.
```

## Why Ray.MediaQuery?

### Zero Implementation Code
Define interfaces, get working repositories. No boilerplate, no mapping configuration.

### SQL Excellence Without Compromise
Use the full power of your database - window functions, CTEs, custom functions. If it runs in your database, it works with Ray.MediaQuery.

### Rich Domain Objects via Dependency Injection
Transform database rows into rich domain objects with business logic, not just data containers:

```php
#[DbQuery('order_detail', factory: OrderDomainFactory::class)]
public function getOrder(string $id): Order;

// Your domain object gets injected services and computed properties
class Order {
    public function __construct(
        public string $id,
        public float $subtotal,
        public float $tax,           // Calculated by TaxService
        public bool $canShip,        // Determined by InventoryService
        private RuleEngine $rules,   // Injected for business logic
    ) {}
    
    public function getPriority(): string {
        return $this->rules->calculatePriority($this);
    }
}
```

### Test Each Layer Independently
SQL queries, factories, and domain objects can all be tested in isolation. When each layer works, the combination works.

### AI-Era Transparency
Unlike ORM magic, everything is explicit and readable - perfect for AI assistants to understand and help with your codebase.

## Core Concept: Interface-Driven Design

Ray.MediaQuery binds PHP interfaces directly to SQL execution. No abstract query builders, no hidden SQL generation, no runtime surprises.

```php
interface TodoRepository
{
    #[DbQuery('add_todo')]
    public function add(string $id, string $title): void;
    
    #[DbQuery('todo_list')]
    /** @return array<Todo> */
    public function findByUser(string $userId): array;
    
    #[DbQuery('stats', factory: StatsFactory::class)]
    public function getStats(string $userId): UserStats;
}
```

The framework handles:
- SQL file discovery and execution
- Parameter binding with type conversion
- Result hydration to entities or arrays
- Factory-based transformations with DI
- Transaction management

You focus on:
- Defining clear interfaces
- Writing efficient SQL
- Implementing business logic

## Quick Start

### Installation

```bash
composer require ray/media-query
```

### Basic Setup

```php
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\MediaQuerySqlModule;
use Ray\AuraSqlModule\AuraSqlModule;

// 1. Configure in your module
class AppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(
            new MediaQuerySqlModule(
                interfaceDir: '/path/to/query/interfaces',
                sqlDir: '/path/to/sql/files'
            )
        );

        $this->install(
            new AuraSqlModule(
                'mysql:host=localhost;dbname=app',
                'username',
                'password'
            )
        );
    }
}

// 2. Define repository interface
interface UserRepository
{
    #[DbQuery('user_add')]
    public function add(string $id, string $name): void;

    #[DbQuery('user_find')]
    public function find(string $id): ?User;
}

// 3. Write SQL files
-- user_add.sql
INSERT INTO users (id, name) VALUES (:id, :name)

-- user_find.sql  
SELECT * FROM users WHERE id = :id

// 4. Get instance and use (no implementation needed!)
$injector = new Injector(new AppModule());
$userRepo = $injector->getInstance(UserRepository::class);

$userRepo->add('user-123', 'Alice');
$user = $userRepo->find('user-123');
```

## Advanced Features

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
    public function getStats(string $id): array;  // ['total' => 10, 'active' => 5]
}
```

**Raw Array (multiple rows):**
```php
interface UserRepository
{
    #[DbQuery('user_list')]
    public function listRaw(): array;  // [['id' => '1', ...], ['id' => '2', ...]]
}
```

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

> **Architecture Pattern**: Factories enable the [**BDR Pattern**](./BDR_PATTERN.md) - combining efficient SQL with rich domain objects through dependency injection.

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
- `getStatement()` - Get PDO statement
- `getPages()` - Get paginated results

## Philosophy: Boundaries That Dissolve

Ray.MediaQuery doesn't fight the impedance mismatch - it dissolves it. SQL and Objects don't need to pretend the other doesn't exist. They can work together, each doing what they do best.

This is more than a technical solution. It's a recognition that different paradigms can coexist harmoniously when we stop trying to force one to be the other.

## Real-World Benefits

- **Performance**: Write optimized SQL without ORM overhead
- **Maintainability**: Clear separation of concerns
- **Testability**: Test SQL and PHP logic independently
- **Flexibility**: Refactor interfaces without touching SQL
- **Transparency**: Every query is visible and optimizable

## Learn More

- [BDR Pattern Guide](./BDR_PATTERN.md)
- [Demo Application](./demo/)
