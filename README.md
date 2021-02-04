# Ray.MediaQuery
![Continuous Integration](https://github.com/ray-di/Ray.MediaQuery/workflows/Continuous%20Integration/badge.svg)

## Overview

`Ray.QueryModule` makes a query to an external media such as a database or Web API with a function object to be injected.


## Motivation


 * You can have a clear boundary between domain layer (usage code) and infrastructure layer (injected function) in code.
 * Execution objects are generated automatically so you do not need to write procedural code for execution.
 * Since usage codes are indifferent to the actual state of external media, storage can be changed later. Easy parallel development and stabbing.

## Composer install

    $ composer require ray/media-query 1.x-dev

## Usage

Defines the interface through which the application accesses media.

```php
interface TodoAddInterface
{
    public function __invoke(string $id, string $title): void;
}

interface TodoItemInterface
{
    /**
     * @return array{id: string, title: string}
     */
    public function __invoke(string $id): array;
}
```

Method with the attribute `DbQuery` to make the target an SQL execution object.

```php
class TodoAdd implements TodoAddInterface
{
    #[DbQuery, Transactional]
    public function __invoke(string $id, string $title): void
    {
    }
}

class TodoItem implements TodoItemInterface
{
    #[DbQuery]
    public function __invoke(string $id): array
    {
    }
}
```

* Prepare the SQL for each in the `$sqlDir/` directory. If the class is `TodoAdd`, it will be `$sqlDir/todo_add.sql`.
* Add a postfix of `item` if the SQL execution returns a single line, or `list` if it returns multiple lines.
* The SQL file can contain multiple SQL statements. The SELECT of the last row will be returned as the execution result.

The SQL execution object will bind and execute the SQL file specified by the query ID with the specified arguments.

```php
assert($todoItem instanceof TodoItemInterface);
print_r(($todoItem)(['id' => '1']));
// ['id' => 1, 'title' => 'run']
```
## Pagination

The `#[Pager]` attribute allows you to paginate a SELECT query in a database.

```php
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;

class TodoList implements TodoListInterface
{
    #[DbQuery, Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): AuraSqlPagerInterface
    {
    }
}
```

The result of the execution is a list object for lazy execution of SQL.
The page object can be obtained by accessing the array by page number, and the number of pages can be obtained by count().

```php
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\Pages;

$pages = ($todoList)();
assert($pages instanceof Pages);
$page = $pages[2]; // array accessをした時にそのページのDBクエリーが行われます。
assert($page instanceof Page);
echo count($pages); // countした時に"count SQL"が生成されクエリーが行われます。

// $page->data // sliced data
// $page->current;
// $page->total
// $page->hasNext
// $page->hasPrevious
// $page->maxPerPage;
// (string) $page // pager html
```

## SqlQuery

The `#[DbQuery]` attribute allows you to create a SQL execution object with less description.
On the other hand, using the `SqlQuery` object requires more description, but gives you more control.

A `SqlQuery` is a type of DAO that executes SQL by specifying the ID of the SQL file instead of SQL.

The following example shows the above attribute method with `SqlQuery`.

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
### SqlQuery API

```php
$sqlQuery->exec($queryId, $params); // no return value
$sqlQuery->getRow($queryId, $params); // result is single row
$sqlQuery->getRowList($queryId, $params); // result is multiple rows
$statement = $sqlQuery->getStatement(); // Retrieve the PDO Statement
```

## Demo

In [demo](/demo), execution objects of `user_add` and `user_item` are created in the above two ways, respectively.
See also the example of injector generation.

```php
php demo/run.php
```
