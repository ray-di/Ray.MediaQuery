# Ray.MediaQuery
![Continuous Integration](https://github.com/ray-di/Ray.MediaQuery/workflows/Continuous%20Integration/badge.svg)

[日本語 (Japanese)](./README.ja.md)

## Overview

`Ray.QueryModule` makes a query to an external media such as a database or Web API with a function object to be injected.


## Motivation


 * You can have a clear boundary between domain layer (usage code) and infrastructure layer (injected function) in code.
 * Execution objects are generated automatically so you do not need to write procedural code for execution.
 * Since usage codes are indifferent to the actual state of external media, storage can be changed later. Easy parallel development and stabbing.

## Composer install

    $ composer require ray/media-query 1.x-dev

## Usage

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

Specify the query interface and install the module.

```php
    protected function configure(): void
    {
        $mediaQueries = [
            UserAddInterface::class,
            UserItemInterface::class
        ];
        $this->install(new MediaQueryModule($this->sqlDir, $mediaQueries));
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

## Pagination

The `#[Pager]` annotation allows you to paginate a SELECT query.

```php
interface TodoList
{
    #[DbQuery, Pager(perPage: 10, template: '/{?page}')]]
    public function __invoke(): Pages
    {
    }
}
```

You can get the number of pages with `count()`, and you can get the page object with array access by page number.
`Pages` is a SQL lazy execution object.

```php
$pages = ($todoList)();
$cnt = count($page); // count SQL is generated and queried when count() is done.
$page = $pages[2]; // When array access is done, DB query for that page is done.

// $page->data // sliced data
// $page->current;
// $page->total
// $page->hasNext
// $page->hasPrevious
// $page->maxPerPage;
// (string) $page // pager html
```

## SqlQuery

`SqlQuery` executes SQL by specifying the ID of an SQL file.
It can be used to prepare implementation classes for detailed implementation.

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
    $this->assertStringContainsString('query:todo_add({"id": "1", "title": "run"})', (string) $this->log);
}
```

Implement your own [MediaQueryLoggerInterface](src/MediaQueryLoggerInterface.php) and run
You can also implement your own [MediaQueryLoggerInterface](src/MediaQueryLoggerInterface.php) to benchmark each media query and log it with the injected PSR logger.
