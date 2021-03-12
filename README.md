# Ray.MediaQuery
[![codecov](https://codecov.io/gh/ray-di/Ray.MediaQuery/branch/master/graph/badge.svg?token=QBOPCUPJQV)](https://codecov.io/gh/ray-di/Ray.MediaQuery)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.MediaQuery/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.MediaQuery)
![Continuous Integration](https://github.com/ray-di/Ray.MediaQuery/workflows/Continuous%20Integration/badge.svg)

[日本語 (Japanese)](./README.ja.md)

## Overview

`Ray.QueryModule` makes a query to an external media such as a database or Web API with a function object to be injected.


## Motivation


 * You can have a clear boundary between domain layer (usage code) and infrastructure layer (injected function) in code.
 * Execution objects are generated automatically so you do not need to write procedural code for execution.
 * Since usage codes are indifferent to the actual state of external media, storage can be changed later. Easy parallel development and stabbing.

## Composer install

    $ composer require ray/media-query

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

### Web API

Specify the Web request ID with the attribute `WebQuery`.

```php
interface PostItemInterface
{
    #[WebQuery('user_item')]
    public function get(string $id): array;
}
```

Create the web api path list file as `web_query.json`.

```json
{
    "$schema": "https://ray-di.github.io/Ray.MediaQuery/schema/web_query.json",
    "webQuery": [
        {"id": "user_item", "method": "GET", "path": "https://{domain}/users/{id}"}
    ]
}
```

### Module

MediaQueryModule binds the execution of SQL and Web API requests to an interface by setting `DbQueryConfig` or `WebQueryConfig` or both.

```php
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\MediaQuery\ApiDomainModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;
use Ray\MediaQuery\WebQueryConfig;

protected function configure(): void
{
    $this->install(
        new MediaQueryModule(
            Queries::fromDir('/path/to/queryInterface'),
            [new DbQueryConfig('/path/to/sql'), new WebQueryConfig('/path/to/web_query.json')],
            new ApiDomainModule(['domain' => 'api.exmaple.com'])
        ),
    );
    $this->install(new AuraSqlModule('mysql:host=localhost;dbname=test', 'username', 'password'));
}
```

### Request object injection

You don't need to provide any implementation classes. It will be generated and injected.

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

### Notes

#### DbQuery

SQL execution is mapped to a method, and the SQL specified by ID is bound and executed by the method argument.
For example, if the ID is `todo_item`, `todo_item.sql` SQL statement will be executed with `['id => $id]` bound.

* Prepare the SQL file in the `$sqlDir` directory.
* Add a postfix of `item` if the return value of the SQL execution is a single line, or `list` if it is multiple lines.
* The SQL file can contain multiple SQL statements. The last line of SELECT will be the return value.

#### Web API

* Customization such as header for authentication is done by binding Guzzle's `ClinetInterface`.

```php
$this->bind(ClientInterface::class)->toProvider(YourGuzzleClientProvicer::class);
```

## Parameters

### DateTime

You can pass a value object as a parameter.
For example, you can specify a `DateTimeInterface` object like this.

```php
interface TaskAddInterface
{
    public function __invoke(string $title, DateTimeInterface $cratedAt = null): void;
}
```

The value will be converted to a date formatted string at SQL execution time or Web API request time.

```sql
INSERT INTO task (title, created_at) VALUES (:title, :createdAt); // 2021-2-14 00:00:00
```

If no value is passed, the bound current time will be injected.
This eliminates the need to hard-code `NOW()` inside SQL and pass the current time every time.

### Test clock

When testing, you can also use a single time binding for the `DateTimeInterface`, as shown below.

```php
$this->bind(DateTimeInterface::class)->to(UnixEpochTime::class);
```

## VO

If a value object other than `DateTime` is passed, the return value of the `ToScalar()` method that implements the `toScalar` interface or the `__toString()` method will be the argument.

```php
interface MemoAddInterface
{
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
INSERT INTO  memo (user_id, memo) VALUES (:user_id, :memo);
```

### Parameter Injection

Note that the default value of `null` for the value object argument is never used in SQL. If no value is passed, the scalar value of the value object injected with the parameter type will be used instead of null.

```php
public function __invoke(Uuid $uuid = null): void; // UUID is generated and passed.
````

## Pagenation

The `#[Pager]` annotation allows paging of SELECT queries.

```php
use Ray\MediaQuery\PagesInterface;

interface TodoList
{
    #[DbQuery, Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): PagesInterface;
}
```

You can get the number of pages with `count()`, and you can get the page object with array access by page number.
`Pages` is a SQL lazy execution object.

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

# SqlQuery

If you pass a `DateTimeIntetface` object, it will be converted to a date formatted string and queried.

```php
$sqlQuery->exec('memo_add', ['memo' => 'run', 'created_at' => new DateTime()]);
```

When an object is passed, it is converted to a value of `toScalar()` or `__toString()` as in Parameter Injection.

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

## Testing Ray.MediaQuery

Here's how to install Ray.MediaQuery from the source and run the unit tests and demos.

```
$ git clone https://github.com/ray-di/Ray.MediaQuery.git
$ cd Ray.MediaQuery
$ composer tests
$ php demo/run.php
```
