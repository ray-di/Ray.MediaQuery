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

Define an interface for media access by adding the attribute `DbQuery` to the method and specifying the SQL ID.

```php
interface TodoAddInterface
{
    #[DbQuery('user_add'), Transactional]
    public function __invoke(string $id, string $title): void;
}
```

```php
interface TodoItemInterface
{
    /**
     * @return array{id: string, title: string}
     */
    #[DbQuery('user_item')]
    public function __invoke(string $id): array;
}
```

Specify a query interface, or a folder of interfaces,

```php
protected function configure(): void
{
    $queries = Queries::fromDir('path/to/Queries');
    $this->install(new MediaQueryModule($queries, $this->sqlDir));
    $this->install(new AuraSqlModule($this->dsn));
}
```

Install the module by specifying the query interface or folder.

```php
protected function configure(): void
{
    $queries = Queries::fromDir('path/to/Queries');
    $this->install(new MediaQueryModule($queries, $this->sqlDir));
    $this->install(new AuraSqlModule($this->dsn));
}
```

You don't need to provide any implementation classes. It will be generated and injected.

```php
class Todo
{
    public function __construct(
        private TodoAddInterface $todoAdd,
        private TodoItemInterface $todoItem
    ) {}

    public function add(string $id, string $title): void
    {
        ($this->todoAdd)($id, $title);
    }

    public function get(string $id): array
    {
        return ($this->todoItem)($id);
    }
}
```

SQL execution is mapped to a method, and the SQL specified by ID is bound to the method argument and executed.
For example, if ID is specified as `todo_item`, `todo_item.sql` SQL statement will be executed with `['id => $id]` bound.

* Prepare each SQL in `$sqlDir/` directory, `$sqlDir/todo_add.sql` if ID is `todo_add`.
  If the ID is `todo_add`, the file is `$sqlDir/todo_add.sql`.
* Add a postfix of `item` if the SQL execution returns a single row, or `list` if it returns multiple rows.
* The SQL file can contain multiple SQL statements, where the last line of the SELECT is the result of the execution.

## Parameter Injection

You can pass a value object to the parameter.
For example, you can specify a `DateTimeInterface` object like this.

```php
interface TaskAddInterface
{
    public function __invoke(string $title, DateTimeInterface $cratedAt = null): void;
}
```

The value will be converted to a date formatted string **at SQL execution time**.

```sql
INSERT INTO task (title, created_at) VALUES (:title, :createdAt); // 2021-2-14 00:00:00
```

If no value is passed, the bound current time will be injected.
This eliminates the need to hard-code `NOW()` inside SQL and pass the current time every time.

When testing, you can also set the `DateTimeInterface` binding to a single time, as follows.

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

Note that the default value of `null` for the value object argument is never used in SQL. If no value is passed, the scalar value of the injected value object will be used instead of null.

## Pagenation

The `#[Pager]` annotation allows paging of SELECT queries.

```php
interface TodoList
{
    #[DbQuery, Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): Pages
    {
    }
}
```

You can get the number of pages with `count()`, and you can get the page object with array access by page number.
`Pages` is a SQL lazy execution object.

```php
$pages = ($todoList)();
$cnt = count($page); // count()をした時にカウントSQLが生成されクエリーが行われます。
$page = $pages[2]; // 配列アクセスをした時にそのページのDBクエリーが行われます。

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

# Web API

To bind an interface to a WebAPI request, add the `WebQuery` attribute and specify the `method` and `uri`, where `uri` is the uri template. The method arguments will be bound to the uri template, and the request object where the Web API request will be made will be created and injected.

```php
interface GetPostInterface
{
    #[WebQuery(method: 'GET', uri: 'https://{domain}/posts/{id}')]
    public function __invoke(string $id): array;
}
```

You can bind Guzzle's ClinetInterface to specify the header for authentication.

```php
$this->bind(ClientInterface::class)->toProvider(YourGuzzleClientProvicer::class);
```

To install, specify the domain to be assigned with the third argument of `MediaQueryModule`.

```php
$module = new MediaQueryModule($mediaQueries, $sqlDir,  ['domain' => 'httpbin.org']);
```

WebQueryの時と同じようにVOを渡す事もできます。

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
