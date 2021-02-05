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

There is no need to prepare an implementation class. It will be generated and injected.

```php
<?php
class User
{
    public function __construct(
        private UserAddInterface $userAdd,
        private UserItemInterface $userItem
    ) {}

    public function add(string $id, string $title): void
    {
        ($this->userAdd)($id, $title);
    }

    public function get(string $id): array
    {
        return ($this->userItem)($id);
    }
}
```

The SQL execution object binds and executes the SQL file specified by the query ID with the specified arguments.
For example, `TodoItem::__invoke()` will bind `todo_item.sql` SQL statement with `['id => $id]` and return the result of the execution.

* Prepare the SQL for each in the `$sqlDir/` directory. If the class is `TodoAdd`, it will be `$sqlDir/todo_add.sql`.
* Add a postfix of `item` if the SQL execution returns a single line, or `list` if it returns multiple lines.
* The SQL file can contain multiple SQL statements. The SELECT of the last row will be returned as the execution result.

## Pagination

The `#[Pager]` attribute allows you to paginate a SELECT query in a database.

```php
interface TodoList
{
    #[DbQuery, Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): Pages
    {
    }
}
```

The result of the execution is a list object for lazy execution of SQL.
The page object can be obtained by accessing the array by page number, and the number of pages can be obtained by count().

```php
$pages = ($todoList)();
$cnt = count($page); // A count SQL will be generated and queried.
$page = $pages[2]; // When an array access is made, a DB query is made for that page.

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

## Get* Method

To retrieve the result of SELECT, Invoke `get*` according to the result to be retrieved.

```php
$sqlQuery->getRow($queryId, $params); // result is a single row
$sqlQuery->getRowList($queryId, $params); // result is multiple rows
$statement = $sqlQuery->getStatement(); // Retrieve the PDO Statement
$pages = $sqlQuery->getPages(); // Get the pager
```
