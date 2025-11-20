# Ray.MediaQuery

**The PHP SQL framework that lets SQL be SQL and Objects be Objects.**

[![codecov](https://codecov.io/gh/ray-di/Ray.MediaQuery/branch/1.x/graph/badge.svg?token=QBOPCUPJQV)](https://codecov.io/gh/ray-di/Ray.MediaQuery)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.MediaQuery/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.MediaQuery)
[![Continuous Integration](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml)

## Stop Fighting the Object-Relational Impedance Mismatch

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

### ✨ **Zero Implementation Code**
Define interfaces, get working repositories. No boilerplate, no mapping configuration.

### 🎯 **SQL Excellence Without Compromise**
Use the full power of your database - window functions, CTEs, custom functions. If it runs in your database, it works with Ray.MediaQuery.

### 🔄 **Rich Domain Objects via Dependency Injection**
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

### 🧪 **Test Each Layer Independently**
SQL queries, factories, and domain objects can all be tested in isolation. When each layer works, the combination works.

### 🤖 **AI-Era Transparency**
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

### 🏗️ Business Domain Repository Pattern

Ray.MediaQuery enables the [**BDR Pattern**](./BDR_PATTERN.md) - combining efficient SQL queries with rich domain objects through dependency injection:

```php
#[DbQuery('complex_report', factory: ReportFactory::class)]
public function getReport(DateTimeInterface $from = null): Report;
// Factory receives DI services to enrich data with business logic
```

### 📄 Pagination Support

```php
#[DbQuery('product_list'), Pager(perPage: 20)]
public function getProducts(): PagesInterface;
```

### 🔄 Value Objects & Type Conversion

```php
// Automatic conversion for DateTime, custom VOs, and more
#[DbQuery('add_task')]  
public function add(string $title, UserId $user, DateTimeInterface $due = null): void;
```

### 📦 Structured Input with Flattening

Keep methods clean with input objects. `#[Input]` attributes automatically flatten nested objects into SQL parameters:

```php
#[DbQuery('user_search')]
public function search(UserSearchInput $input): array;
// Nested objects are flattened: input.address.city becomes :city
```

### 🎯 Flexible Result Mapping

- Arrays for simple data
- Entity hydration for domain objects
- Factory transformation for complex logic
- Custom result processors

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

- [📚 Full Documentation](https://ray-di.github.io/Ray.MediaQuery/)
- [🏗️ BDR Pattern Guide](./BDR_PATTERN.md)
- [💻 Demo Application](./demo/)
