# MediaQuery

インターフェイスのメソッドに`#[DbQuery('...')]`属性をつけると、AOPによりメソッドがSQL実行メソッドになります。

## Getting Started

SQLファイルを保存します。

```sql
INSERT INTO user (id, name) VALUES (:id, :name); -- $sqlDir/user_add.sql
SELECT * FROM user WHERE id = :id; -- $sqlDir/user_item.sql
```

アプリケーションがメディアアクセスするインターフェイスを定義します。

```php
use Ray\AuraSqlModule\Annotation\Transactional;
use Ray\MediaQuery\Annotation\DbQuery;

interface UserAddInterface
{
    #[DbQuery('user_add'), Transactional]
    public function __invoke(string $id, string $name): void;
}
```

```php
use Ray\MediaQuery\Annotation\DbQuery;

interface UserItemInterface
{
    /**
     * @return array{id: string, name: string}
     */
    #[DbQuery('user_item', type: 'row')]
    public function __invoke(string $id): array;
}
```

## インスタンス取得

```php
use Aura\Sql\ExtendedPdoInterface;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;

$sqlDir = __DIR__ . '/sql';
$queries = Queries::fromClasses([
    UserAddInterface::class,
    UserItemInterface::class
]);

$injector = new Injector(new class($queries, $sqlDir) extends AbstractModule {
    public function __construct(
        private Queries $queries,
        private string $sqlDir
    ) {
    }

    protected function configure(): void
    {
        $this->install(new MediaQueryModule(
            $this->queries,
            [new DbQueryConfig($this->sqlDir)]
        ));
        $this->install(new AuraSqlModule('sqlite::memory:'));
    }
});

$pdo = $injector->getInstance(ExtendedPdoInterface::class);
$pdo->query('CREATE TABLE user (id TEXT, name TEXT)');

$userAdd = $injector->getInstance(UserAddInterface::class);
$userItem = $injector->getInstance(UserItemInterface::class);

$userAdd('1', 'koriym');
print_r($userItem('1'));
// ['id' => '1', 'name' => 'koriym']
```

メソッドの引数は名前でバインドされSQLが実行されます。
例えば、`UserItemInterface::__invoke()`は`user_item.sql`に`['id' => $id]`をバインドして実行した結果を返します。

* `#[DbQuery('user_add')]`は`$sqlDir/user_add.sql`に対応します。
* SQL実行が単一行を返す時には`type: 'row'`を指定します。
* SQLファイルには複数のSQL文を記述できます。最後の`SELECT`が実行結果として返ります。

インターフェイスのディレクトリとSQLディレクトリが決まっている時には、`MediaQuerySqlModule`を使って設定を短くすることもできます。

## Pagination

`#[Pager]`アノテーションで、データベースの`SELECT`クエリーをページングする事ができます。

```php
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface UserListInterface
{
    #[DbQuery('user_list'), Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): Pages;
}
```

実行結果の`Pages`は、配列アクセスでページを取得したり`count()`で全体の件数を取得することができます。

```php
$userList = $injector->getInstance(UserListInterface::class);
$pages = $userList();
$page = $pages[1];
$total = count($pages);
```
