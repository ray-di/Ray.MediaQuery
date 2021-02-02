# Ray.MediaQuery

## Overview

`Ray.QueryModule` makes a query to an external media such as a database or Web API with a function object to be injected.


## Motivation


 * You can have a clear boundary between domain layer (usage code) and infrastructure layer (injected function) in code.
 * Execution objects are generated automatically so you do not need to write procedural code for execution.
 * Since usage codes are indifferent to the actual state of external media, storage can be changed later. Easy parallel development and stabbing.

## Installation

### Composer install

    $ composer require ray/media-query

### Usage

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

メソッドに`DbQuery`と属性をつけて実装を記述します。

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

## インジェクター

`AuraSqlModule`と`MediaQueryModule`をインストールしてインジェクターを生成します。

```php
$injector = new Injector(new class(string $sqlDir, string $dsn) extends AbstractModule {
    private function __construct(
        private string $sqlDir;
        private string $dsn;
    ){}
    
    protected function configure()
    {
        $this->install(MediaQueryModule($this->sqlDir);    
        $this->install(AuraSqlModule($this->dsn);    
        $this->bind(TodoAddInterface::class)->to(TodoAdd::class);
        $this->bind(TodoItemInterface::class)->to(TodoItem::class);
    }
});
$injector = new Injector($module);
```

下記のクラスはアプリケーションの例です。

```php
$foo = $injector->getInstance(Foo::class);

class Foo
{
    public function __construct(
        private TodoAddInterface $userAdd,
        private TodoItemInterface $userItem
    ) {}
    
    public function add(string $id, string $title): void
    {
        ($this->userAdd)('id1', 'run');
    }
    
    public function get(string $id): array
    {
        return ($this->userItem)('id1');
    }
}

## SqlQuery

`SqlQuery`はDAOの一種で、SQLの代わりにSQLファイルのIDを指定してSQLを実行します。

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

```php
$sqlQyery->exec($queryId, $params); // 返り値なし
$sqlQyery->getRow($queryId, $params); // 結果が単数行
$sqlQyery->getRowList($queryId, $params); // 結果が複数行
$statement = $sqlQyery->getStatement(); // PDO Statementを取得
```
 