# Ray.MediaQuery

## Database access mapping framework
[![codecov](https://codecov.io/gh/ray-di/Ray.MediaQuery/branch/1.x/graph/badge.svg?token=QBOPCUPJQV)](https://codecov.io/gh/ray-di/Ray.MediaQuery)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.MediaQuery/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.MediaQuery)
[![Continuous Integration](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml)

[日本語 (Japanese)](./README-ja.md)

## Overview

`Ray.MediaQuery` provides database query abstraction through interface-based query definitions.

## Motivation

 * This framework provides database query abstraction through interface-based query definitions.
 * Execution objects are generated automatically so you do not need to write procedural code for execution.
 * Since usage codes are indifferent to the actual state of external media, storage can be changed later. Easy parallel development and stubbing.

## Composer install

    $ composer require ray/media-query

For Web API queries, install the separate package:

    $ composer require ray/web-query

> **Note:** For migration from older versions, see [MIGRATION.md](./MIGRATION.md).

## Getting Started

Define the interface for media access.

### DB

Specify the SQL ID with the attribute `DbQuery`.

```php
interface TodoAddInterface
{
    #[DbQuery('user_add')]
    public function add(string $id, string $title): void;
}
```

### Module

MediaQueryModule binds the execution of SQL to an interface by setting `DbQueryConfig`.

```php
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;

protected function configure(): void
{
    $this->install(
        new MediaQueryModule(
            Queries::fromDir('/path/to/queryInterface'),
            new DbQueryConfig('/path/to/sql')
        ),
    );
    $this->install(new AuraSqlModule('mysql:host=localhost;dbname=test', 'username', 'password'));
}
```

Note: MediaQueryModule requires AuraSqlModule to be installed.

### Request object injection

You do not need to prepare an implementation class. It is generated and injected from the interface.

```php
class Todo
{
    public function __construct(
        private TodoAddInterface $todoAdd
    ) {}

    public function add(string $id, string $title): void
    {
        $this->todoAdd->add($id, $title);
    }
}
```

### DbQuery

When the method is called, the SQL specified by the ID is bound with the method argument and executed.
For example, if the ID is `todo_item`, the `todo_item.sql` SQL statement is bound with `['id => $id]` and executed.

```php
interface TodoItemInterface
{
    #[DbQuery('todo_item', type: 'row')]
    public function item(string $id): array;

    #[DbQuery('todo_list')]
    /** @return array<Todo> */
    public function list(string $id): array;
}
```

- If the result is a `row`(`array<string, scalar>`), specify `type:'row'`. The type is not necessary for `row_list`(`array<int, array<string, scalar>>`).
- SQL files can contain multiple SQL statements. In that case, the return value is the last line of the SELECT.

#### Entity

When the return value of a method is an entity class, the result of the SQL execution is hydrated.

```php
interface TodoItemInterface
{
    #[DbQuery('todo_item')]
    public function item(string $id): Todo;

    #[DbQuery('todo_list')]
    /** @return array<Todo> */
    public function list(string $id): array;
}
```

```php
final class Todo
{
    public readonly string $id;
    public readonly string $title;
}
```

**Entity classes should use constructor property promotion (recommended):**

For multi-word database columns (snake_case), mix constructor property promotion with explicit assignment:

```php
final class Invoice
{
    public readonly string $userName;
    public readonly string $emailAddress;
    
    public function __construct(
        public readonly string $id,        // Single word - direct mapping
        public readonly string $title,     // Single word - direct mapping
        string $user_name,                 // Multi-word - explicit assignment
        string $email_address,             // Multi-word - explicit assignment
    ) {
        $this->userName = $user_name;
        $this->emailAddress = $email_address;
    }
}
```

**PHP 8.4+ readonly class:**

```php
final readonly class Invoice
{
    public string $userName;
    public string $emailAddress;
    
    public function __construct(
        public string $id,           // Single word - direct mapping
        public string $title,        // Single word - direct mapping  
        string $user_name,           // Multi-word - explicit assignment
        string $email_address,       // Multi-word - explicit assignment
    ) {
        $this->userName = $user_name;
        $this->emailAddress = $email_address;
    }
}
```

**Usage with database queries:**

```php
interface UserInterface
{
    #[DbQuery('user_list')]
    /** @return array<Invoice> */
    public function getUsers(): array;
}

// SQL file: user_list.sql  
// SELECT id, title, user_name, email_address FROM invoices

// PDO::FETCH_CLASS works directly - constructor parameters match column names!
// - id → $id (direct)
// - title → $title (direct)  
// - user_name → $user_name parameter → $userName property
// - email_address → $email_address parameter → $emailAddress property
```

**Benefits:**
- Type safety with strict typing
- Immutability with `readonly` properties  
- Better IDE support and refactoring
- No magic method overhead
- Clear snake_case ↔ camelCase mapping

**Note:** Constructor-less entities (with public properties) are discouraged as they lack type safety and immutability benefits of modern PHP.

```php
final class Todo
{
    public function __construct(
        public readonly string $id,
        public readonly string $title
    ) {}
}
```

#### Entity factory

To create an entity with a factory class, specify the factory class in the `factory` attribute.

```php
interface TodoItemInterface
{
    #[DbQuery('todo_item', factory: TodoEntityFactory::class)]
    public function item(string $id): Todo;

    #[DbQuery('todo_list', factory: TodoEntityFactory::class)]
    /** @return array<Todo> */
    public function list(string $id): array;
}
```

The `factory` method of the factory class is called with the fetched data. You can also change the entity depending on the data.

```php
final class TodoEntityFactory
{
    public static function factory(string $id, string $name): Todo
    {
        return new Todo($id, $name);
    }
}
```

If the factory method is not static, the factory class dependency resolution is performed.

```php
final class TodoEntityFactory
{
    public function __construct(
        private HelperInterface $helper
    ){}
    
    public function factory(string $id, string $name): Todo
    {
        return new Todo($id, $this->helper($name));
    }
}
```

#### Advanced Factory Usage

Factories enable powerful transformations beyond simple database mapping:

**Add computed properties:**
```php
final class OrderEntityFactory
{
    public function factory(string $id, float $amount): Order
    {
        return new Order(
            id: $id,
            amount: $amount,
            tax: $amount * 0.1,          // Computed tax
            total: $amount * 1.1,        // Computed total
        );
    }
}
```

**Transform data with injected services:**
```php
final class UserEntityFactory
{
    public function __construct(
        private EmailValidator $emailValidator,  // Injected by DI
    ) {}
    
    public function factory(string $id, string $first_name, string $last_name, string $email): User
    {
        return new User(
            id: $id,
            firstName: $first_name,
            lastName: $last_name,
            fullName: "$first_name $last_name",              // Computed
            email: $this->emailValidator->validate($email),  // Validated with DI service
        );
    }
}
```

> **🏗️ Architecture Pattern**: Ray.MediaQuery enables the [**Business Domain Repository Pattern (BDR Pattern)**](./BDR_PATTERN.md) - an approach that transforms simple database queries into rich domain objects through dependency injection and business logic integration.

### Web API

**Web API functionality has been moved to a separate package.** 

For Web API queries, please see the [ray/web-query](https://github.com/ray-di/Ray.WebQuery) package documentation.

## Parameters

### DateTime

You can pass a value object as a parameter.
For example, you can specify a `DateTimeInterface` object like this.

```php
interface TaskAddInterface
{
    #[DbQuery('task_add')]
    public function __invoke(string $title, DateTimeInterface $cratedAt = null): void;
}
```

The value will be converted to a date formatted string at SQL execution time.

```sql
INSERT INTO task (title, created_at) VALUES (:title, :createdAt); # 2021-2-14 00:00:00
```

If no value is passed, the bound current time will be injected.
This eliminates the need to hard-code `NOW()` inside SQL and pass the current time every time.

### Test clock

When testing, you can also use a single time binding for the `DateTimeInterface`, as shown below.

```php
$this->bind(DateTimeInterface::class)->to(UnixEpochTime::class);
```

## VO

If a value object other than `DateTime` is passed, the return value of the `toScalar()` method that implements the `ToScalar` interface or the `__toString()` method will be the argument.

```php
interface MemoAddInterface
{
    #[DbQuery('memo_add')]
    public function __invoke(string $memo, UserId $userId = null): void;
}
```

```php
class UserId implements ToScalarInterface
{
    public function __construct(
        private LoginUser $user;
    ){}
    
    public function toScalar(): int
    {
        return $this->user->id;
    }
}
```

```sql
INSERT INTO memo (user_id, memo) VALUES (:userId, :memo);
```

### Parameter Injection

Note that the default value of `null` for the value object argument is never used in SQL. If no value is passed, the scalar value of the value object injected with the parameter type will be used instead of null.

```php
public function __invoke(Uuid $uuid = null): void; // UUID is generated and passed.
````

## Input Object Flattening

When using [Ray.InputQuery](https://github.com/ray-di/Ray.InputQuery), objects with the `#[Input]` attribute are automatically flattened to SQL parameters. This allows for structured input while maintaining flat database queries.

```php
use Ray\InputQuery\Attribute\Input;

final class UserInput
{
    public function __construct(
        #[Input] public readonly string $givenName,
        #[Input] public readonly string $familyName,
        #[Input] public readonly string $email
    ) {}
}

final class TodoCreateInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly UserInput $assignee,  // nested input
        #[Input] public readonly ?DateTimeInterface $dueDate
    ) {}
}

interface TodoInterface
{
    #[DbQuery('todo_create')]
    public function create(TodoCreateInput $input): void;
}
```

The nested structure is flattened for SQL binding:

```php
// Input object:
TodoCreateInput {
    title: "Buy milk",
    assignee: UserInput {
        givenName: "John",
        familyName: "Doe",
        email: "john@example.com"
    },
    dueDate: DateTime("2024-01-15")
}

// Flattened parameters for SQL:
[
    "title" => "Buy milk",
    "givenName" => "John",      // directly from UserInput
    "familyName" => "Doe",      // directly from UserInput  
    "email" => "john@example.com", // directly from UserInput
    "dueDate" => "2024-01-15 00:00:00"  // DateTime converted
]
```

This feature provides:
- **Type-safe input structures** while keeping SQL simple
- **Resilience to refactoring** - object structure changes don't break SQL bindings
- **Automatic parameter conversion** - DateTime and other value objects are converted as usual

Note: Only objects with `#[Input]` attributes on their constructor parameters are flattened. Regular objects are passed through to the existing ParamConverter.

## Pagination

The `#[Pager]` annotation allows paging of SELECT queries.

```php
use Ray\MediaQuery\PagesInterface;

interface TodoList
{
    #[DbQuery('todo_list'), Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): PagesInterface;
}
```

You can get the number of pages with `count()`, and you can get the page object with array access by page number.
`Pages` is a SQL lazy execution object.

The number of items per page is specified by `perPage`, but for dynamic values, specify a string with the name of the argument representing the number of pages as follows

```php
    #[DbQuery('todo_list'), Pager(perPage: 'pageNum', template: '/{?page}')]
    public function __invoke($pageNum): Pages;
```

```php
$pages = ($todoList)();
$cnt = count($page); // When count() is called, the count SQL is generated and queried.
$page = $pages[2]; // A page query is executed when an array access is made.

// $page->data // sliced data
// $page->current;
// $page->total
// $page->hasNext
// $page->hasPrevious
// $page->maxPerPage;
// (string) $page // pager html
```

Use `@return` to specify hydration to the entity class.

```php
    #[DbQuery('todo_list'), Pager(perPage: 'pageNum', template: '/{?page}')]
    /** @return array<Todo> */
    public function __invoke($pageNum): Pages;
```

# SqlQuery

`SqlQuery` executes SQL by specifying the ID of the SQL file.
It is used when detailed implementations with an implementation class.

```php
class TodoItem implements TodoItemInterface
{
    public function __construct(
        private SqlQueryInterface $sqlQuery
    ){}

    public function __invoke(string $id) : array
    {
        return $this->sqlQuery->getRow('todo_item', ['id' => $id]);
    }
}
```

## Get* Method

To get the SELECT result, use `get*` method depending on the result you want to get.

```php
$sqlQuery->getRow($queryId, $params); // Result is a single row
$sqlQuery->getRowList($queryId, $params); // result is multiple rows
$statement = $sqlQuery->getStatement(); // Retrieve the PDO Statement
$pages = $sqlQuery->getPages(); // Get the pager
```

Ray.MediaQuery contains the [Ray.AuraSqlModule](https://github.com/ray-di/Ray.AuraSqlModule).
If you need more lower layer operations, you can use Aura.Sql's [Query Builder](https://github.com/ray-di/Ray.AuraSqlModule#query-builder) or [Aura.Sql](https://github.com/auraphp/Aura.Sql) which extends PDO.
[doctrine/dbal](https://github.com/ray-di/Ray.DbalModule) is also available.

## Profiler

Media accesses are logged by a logger. By default, a memory logger is bound to be used for testing.

```php
public function testAdd(): void
{
    $this->sqlQuery->exec('todo_add', $todoRun);
    $this->assertStringContainsString('query: todo_add({"id": "1", "title": "run"})', (string) $this->log);
}
```

Implement your own [MediaQueryLoggerInterface](src/MediaQueryLoggerInterface.php) and run
You can also implement your own [MediaQueryLoggerInterface](src/MediaQueryLoggerInterface.php) to benchmark each media query and log it with the injected PSR logger.

### SQL Template Configuration

You can customize the SQL logging format using the `MediaQuerySqlTemplateModule`. This module allows you to define a template for how SQL queries are formatted in logs.

```php
use Ray\MediaQuery\MediaQuerySqlTemplateModule;

protected function configure(): void
{
    // Default template: "-- {{ id }}.sql\n{{ sql }}"
    $this->install(new MediaQuerySqlTemplateModule());
    
    // Custom template with application name
    $this->install(new MediaQuerySqlTemplateModule("-- MyApp: {{ id }}.sql\n{{ sql }}"));
}
```

Available template variables:
- `{{ id }}`: The identifier for the SQL query
- `{{ sql }}`: The SQL query string itself

Example output with custom template:
```sql
-- MyApp: user_list.sql
SELECT id, name, email FROM users WHERE status = :status
```
### PerformSql Interface

For advanced SQL execution control, you can inject the `PerformSqlInterface` which provides direct access to the SQL execution layer.

Example)
 * Injecting the ID of a logged-in user and leaving it as a comment statement in SQL.
 * Leave bound values in comments or logs during development

## Annotations / Attributes

You can use either [doctrine annotations](https://github.com/doctrine/annotations/) or [PHP8 attributes](https://www.php.net/manual/en/language.attributes.overview.php) can both be used. 
The next two are the same.

```php
use Ray\MediaQuery\Annotation\DbQuery;

#[DbQuery('user_add')]
public function add1(string $id, string $title): void;

/** @DbQuery("user_add") */
public function add2(string $id, string $title): void;
```

## Testing Ray.MediaQuery

Here's how to install Ray.MediaQuery from the source and run the unit tests and demos.

```
$ git clone https://github.com/ray-di/Ray.MediaQuery.git
$ cd Ray.MediaQuery
$ composer tests
$ php demo/run.php
```

## PHP 8.4 Support and Aura.Sql

This library supports PHP 8.1 to 8.4.
Aura.Sql has different major versions for different PHP versions:

- Aura.Sql v5.x: Recommended for PHP 8.1 - 8.3.
- Aura.Sql v6.x: Recommended for PHP 8.4 and newer.

Our `composer.json` specifies `aura/sql: "^5 || ^6"` to allow flexibility.

**Important for PHP 8.4 users:**

If you are using PHP 8.4, it is highly recommended to ensure Aura.Sql v6.x is installed.
Due to how Composer resolves dependencies with `--prefer-lowest`, Aura.Sql v5.x (specifically older patch versions like 5.0.0 whose `composer.json` might not have an upper PHP bound like `<8.4`) might be installed on PHP 8.4 if you explicitly use `--prefer-lowest` or if other constraints lead to it. While our CI tests for PHP 8.4 with `lowest` dependencies are configured to force Aura.Sql v6, your local environment or specific project setup might differ.

To ensure Aura.Sql v6 is used on PHP 8.4, you can:
1.  Run `composer require aura/sql:"^6.0"` in your project.
2.  If `aura/sql` is already in your `composer.json`, ensure its constraint points to `^6.0` or a similar range that selects v6.
