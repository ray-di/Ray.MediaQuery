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
実行結果のページャー([Pages](Page.md))は、配列アクセスでページを取得したりcount()で全体の件数を取得することができます。

