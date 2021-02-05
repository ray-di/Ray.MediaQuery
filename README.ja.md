# Ray.MediaQuery

## Overview

`Ray.MediaQuery` makes a query to an external media such as a database or Web API with a function object to be injected.

## Motivation

* You can have a clear boundary between domain layer (usage code) and infrastructure layer (injected function) in code.
* Execution objects are generated automatically so you do not need to write procedural code for execution.
* Since usage codes are indifferent to the actual state of external media, storage can be changed later. Easy parallel development and stabbing.

## Composer install

    $ composer require ray/media-query

## Componests

* SqlQuery
* MediaQuery

2つのコンポーネントが用意されています。
1つはSQLの実行をSQL IDで行うローレベルなSqlQuery、もう1つはメソッドをSQL実行に置き換えるMediaQueryです。

# SqlQuery

`SqlQuery`はSQL文の代わりにSQLファイルのIDを指定してSQLを実行します。

## Getting Started

SQLファイルを`$sqlDir/user_add.sql`保存します。

```sql
INSERT INTO user (id, name) VALUES (:id, :name);
```

SQLディレクトリとDSNを渡して`SqlQuery`オブジェクトを取得し、
execで`user_add.sql`のSQLが実行されます。

```php
$sqlQuery = SqlQueryFactory::getInstance($sqlDir, 'sqlite::memory:');
\$sqlQuery->exec('user_add', ['id' => '1', 'name' => 'ray');
```

1つのファイルに複数のSQLを記述できます。`;`で区切ってください。

## Get* Method

```php
$sqlQuery->getRow($queryId, $params); // 結果が単数行
$sqlQuery->getRowList($queryId, $params); // 結果が複数行
$statement = $sqlQuery->getStatement(); // PDO Statementを取得
```

getPages()はページングされたSQLを遅延実行する[Pages](docs/pages.md)オブジェクトを取得します。

```php
$pages = $sqlQuery->getPages();
```

# MediaQuery

インターフェイスを実装した空のメソッドに`#[DbQuery]`の属性をつけると、AOPによりメソッドがSQL実行メソッドになります。

## Getting Started

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

メソッドに`DbQuery`と属性をつけて、メソッドをSQL実行メソッドにします。

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

インスタンス取得

```
$module = new MediaQueryModule(dirname(__DIR__) . '/tests/sql', new AuraSqlModule('sqlite::memory:'));
$todo = (new Injector($module))->getInstance(Todo::class);
```
メソッドの引数でバインドされSQLが実行されます。
例えば、`TodoItem::__invoke()`は`todo_item.sql`SQL文に`['id => $id]`をバインドして実行した結果を返します。

```php
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

実行結果はページングされたSQLを遅延実行する[Pages](docs/pages.md)オブジェクトです。

## Demo

[demo](/demo)では上記２種類のやり方で、それぞれ`user_add`、`user_item`の実行オブジェクトを作成しています。
インジェクター生成の例と合わせてご覧ください。

```php
php demo/run.php
```
