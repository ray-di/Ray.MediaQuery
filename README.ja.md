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

class TodoItem implements TodoItemInterface
{
    #[DbQuery]
    public function __invoke(string $id): array
    {
    }
}
```

 * `$sqlDir/`ディレクトリにそれぞれのSQLを用意します。クラスが`TodoAdd`なら`$sqlDir/todo_add.sql`です。
 * SQL実行が返すの単一行なら`item`、複数行なら`list`のpostfixを付けます。
 * SQLファイルには複数のSQL文が記述できます。最後の行のSELECTが実行結果として返ります。

SQL実行オブジェクトは、クエリーIDで指定されたSQLファイルを指定された引数でバインドして実行します。

```php
assert($todoItem instanceof TodoItemInterface);
print_r(($todoItem)(['id' => '1']));
// ['id' => 1, 'title' => 'run']
```

## SqlQuery

`#[DbQuery]`アトリビュートは少ない記述でSQL実行オブジェクトを生成できます。
一方、`SqlQuery`オブジェクトを使うと記述は増えますが、より多くの制御ができます。

`SqlQuery`はDAOの一種です。SQLの代わりにSQLファイルのIDを指定してSQLを実行します。

下記の例は上記アトリビュートの方法を`SqlQuery`で行った場合の例です。

```php
class TodoItem implements TodoItemInterface
{
    /** @var SqlQueryInterface */
    private $sqlQuery;

    public function __construct(SqlQueryInterface $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    public function __invoke(string $id) : array
    {
        return $this->sqlQuery->getRow('todo_item', ['id' => $id]);
    }
}
```
### SqlQuery API

```php
$sqlQyery->exec($queryId, $params); // 返り値なし
$sqlQyery->getRow($queryId, $params); // 結果が単数行
$sqlQyery->getRowList($queryId, $params); // 結果が複数行
$statement = $sqlQyery->getStatement(); // PDO Statementを取得
```

## Demo

[demo](/demo)では上記２種類のやり方で、それぞれ`user_add`、`user_item`の実行オブジェクトを作成しています。
インジェクター生成の例と合わせてご覧ください。
 
```php
php demo/run.php
```