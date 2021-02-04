# Ray.MediaQuery

## Overview

`Ray.MediaQuery` makes a query to an external media such as a database or Web API with a function object to be injected.

## Motivation

 * You can have a clear boundary between domain layer (usage code) and infrastructure layer (injected function) in code.
 * Execution objects are generated automatically so you do not need to write procedural code for execution.
 * Since usage codes are indifferent to the actual state of external media, storage can be changed later. Easy parallel development and stabbing.

## Composer install

    $ composer require ray/media-query

## Usage

アプリケーションがメディアアクセスするインターフェイスを定義します。

```php
interface TodoAddInterface
{
    public function __invoke(string $id, string $title): void;
}
```

```php

interface TodoItemInterface
{
    /**
     * @return array{id: string, title: string}
     */
    public function __invoke(string $id): array;
}
```

メソッドに`DbQuery`と属性をつけて、対象をSQL実行オブジェクトにします。

```php
class TodoAdd implements TodoAddInterface
{
    #[DbQuery, Transactional]
    public function __invoke(string $id, string $title): void
    {
    }
}
```

```php
class TodoItem implements TodoItemInterface
{
    #[DbQuery]
    public function __invoke(string $id): array
    {
    }
}
```
SQL実行オブジェクトは、クエリーIDで指定されたSQLファイルを指定された引数でバインドして実行します。
例えば、`TodoItem::__invoke()`は`todo_item.sql`SQL文に`['id => $id]`をバインドして実行した結果を返します。

```php
assert($todoItem instanceof TodoItemInterface);
print_r(($todoItem)(id: '1'));
// ['id' => 1, 'title' => 'run']
```

 * `$sqlDir/`ディレクトリにそれぞれのSQLを用意します。クラスが`TodoAdd`なら`$sqlDir/todo_add.sql`です。
 * SQL実行が返すの単一行なら`item`、複数行なら`list`のpostfixを付けます。
 * SQLファイルには複数のSQL文が記述できます。最後の行のSELECTが実行結果として返ります。

## Pagination

`#[Pager]`アノテーションで、データベースのSELECTクエリーをページングする事ができます。

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

ページャーオブジェクト(`AuraSqlPagerInterface`)を取得して、
ページ番号で配列アクセスするとその時点でDBクエリーが行われページオブジェクトが取得できます。

```php
use \Ray\AuraSqlModule\Pagerfanta\Page;

$pager = ($todoList)();
assert($pager instanceof AuraSqlPagerInterface);
$page = $pager[2]; // array accessをした時にそのページのDBクエリーが行われます。
assert($page instanceof Page);

// $page->data // sliced data
// $page->current;
// $page->total
// $page->hasNext
// $page->hasPrevious
// $page->maxPerPage;
// (string) $page // pager html
```

## SqlQuery

`#[DbQuery]`アトリビュートは少ない記述でSQL実行オブジェクトを生成できます。
一方、`SqlQuery`オブジェクトを使うと記述は増えますが、より多くの制御ができます。

`SqlQuery`はDAOの一種です。SQLの代わりにSQLファイルのIDを指定してSQLを実行します。

下記の例は上記アトリビュートの方法を`SqlQuery`で行った場合の例です。

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
$sqlQuery->exec($queryId, $params); // 返り値なし
$sqlQuery->getRow($queryId, $params); // 結果が単数行
$sqlQuery->getRowList($queryId, $params); // 結果が複数行
$statement = $sqlQuery->getStatement(); // PDO Statementを取得
```

## Demo

[demo](/demo)では上記２種類のやり方で、それぞれ`user_add`、`user_item`の実行オブジェクトを作成しています。
インジェクター生成の例と合わせてご覧ください。
 
```php
php demo/run.php
```
