# Ray.MediaQuery

## 概要

`Ray.MediaQuery` 外部メディアのクエリーのインターフェイスを実行オブジェクトに変えインジェクトします。

* ドメイン層とインフラ層の境界をコードで明確に持つことができます。
* インターフェイスからリクエストオブジェクトが生成されるので、実行のための手続き的なコードを書く必要がありません。
* 利用コードは外部メディアの実体には無関係なので、後からストレージを変更することができます。並列開発やスタッビングが容易です。

## インストール

    $ composer require ray/media-query

## 利用方法

メディアアクセスするインターフェイスを定義します。
メソッドに`DbQuery`の属性をつけて、SQLのIDを指定します。

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

クエリーインターフェイス、またはのフォルダを指定して、モジュールをインストールします。

```php
protected function configure(): void
{
    $queries = Queries::fromDir('path/to/Queries');
    $this->install(new MediaQueryModule($queries, $this->sqlDir));
    $this->install(new AuraSqlModule($this->dsn));
}
```

実装クラスを用意する必要はありません。生成され、インジェクトされます。

```php
<?php
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

SQL実行がメソッドにマップされ、IDで指定されたSQLをメソッドの引数でバインドして実行します。
例えばIDが`todo_item`の指定では`todo_item.sql`SQL文に`['id => $id]`をバインドして実行します。

* `$sqlDir/`ディレクトリにそれぞれのSQLを用意します。IDが`todo_add`なら`$sqlDir/todo_add.sql`です。
* SQL実行が返すのが単一行なら`item`、複数行なら`list`のpostfixを付けます。
* SQLファイルには複数のSQL文が記述できます。最後の行のSELECTが実行結果になります。

## パラメーターインジェクション

パラメーターにバリューオブジェクトを渡すことができます。
例えば、`DateTimeInterface`オブジェクトをこのように指定できます。

```php
interface TaskAddInterface
{
    public function __invoke(string $title, DateTimeInterface $cratedAt = null): void;
}
```

値は**SQL実行時に**日付フォーマットされた文字列に変換されます。

```sql
INSERT INTO task (title, created_at) VALUES (:title, :createdAt); // 2021-2-14 00:00:00
```

値を渡さないとバインドされている現在時刻がインジェクションされます。
SQL内部で`NOW()`とハードコーディングする事や、毎回現在時刻を渡す手間を省きます。

テストの時には以下のように`DateTimeInterface`の束縛を１つの時刻にする事もできます。

```php
$this->bind(DateTimeInterface::class)->to(UnixEpochTime::class);
```

## VO

`DateTime`以外のバリューオブジェクトが渡されると`toScalar`インターフェイスを実装した`ToScalar()`メソッド、もしくは`__toString()`メソッドの返り値が引数になります。

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

バリューオブジェクトの引数のデフォルトの値の`null`がSQLで使われることは無い事に注意してください。値が渡されないと、nullの代わりにインジェクトされたバリューオブジェクトのスカラー値が使われます。

## SqlQuery

`DateTimeIntetface`オブジェクトを渡すと、日付フォーマットされた文字列に変換されてクエリーが行われます。

```php
$sqlQuery->exec('memo_add', ['memo' => 'run', 'created_at' => new DateTime()]);
```

オブジェクトが渡されるとParameter Injectionと同様`toScalar()`または`__toString()`の値に変換されます。

## ページネーション

`#[Pager]`アノテーションで、SELECTクエリーをページングする事ができます。

```php
interface TodoList
{
    #[DbQuery, Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): Pages
    {
    }
}
```

`count()`で件数が取得でき、ページ番号で配列アクセスをするとページオブジェクトが取得できます。
`Pages`はSQL遅延実行オブジェクトです。

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

## SqlQuery

`SqlQuery`はSQLファイルのIDを指定してSQLを実行します。
実装クラスを用意して詳細な実装を行う時に使用します。

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

`DateTimeIntetface`オブジェクトを渡すと、日付フォーマットされた文字列に変換されてクエリーが行われます。

```php
$sqlQuery->exec('memo_add', ['created_at' => new DateTime()]);
```

## Get* メソッド

SELECT結果を取得するためには取得する結果に応じた`get*`を使います。

```php
$sqlQuery->getRow($queryId, $params); // 結果が単数行
$sqlQuery->getRowList($queryId, $params); // 結果が複数行
$statement = $sqlQuery->getStatement(); // PDO Statementを取得
$pages = $sqlQuery->getPages(); // ページャーを取得
```

Ray.MediaQueryは[Ray.AuraSqlModule](https://github.com/ray-di/Ray.AuraSqlModule) を含んでいます。
さらに低レイヤーの操作が必要な時はAura.Sqlの[Query Builder](https://github.com/ray-di/Ray.AuraSqlModule#query-builder) やPDOを拡張した[Aura.Sql](https://github.com/auraphp/Aura.Sql) のExtended PDOをお使いください。
[doctrine/dbal](https://github.com/ray-di/Ray.DbalModule) も利用できます。

## バリューオブジェクト

`DateTimeIntetface`オブジェクトを渡すと、日付フォーマットされた文字列に変換されてクエリーが行われます。

```php
$sqlQuery->exec('memo_add', ['memo' => 'run', 'created_at' => new DateTime()]);
```

オブジェクトが渡されるとParameter Injectionと同様`toScalar()`または`__toString()`の値に変換されます。

# Web API

インターフェイスをWebAPIリクエストにバインドするためには`WebQuery`の属性をつけ、`method`と`uri`を指定し`uri`はuri templateを指定します。メソッドの引数がuri templateにバインドされ、Web APIリクエストが行われるリクエストオブジェクトが生成されインジェクトされます。

```php
interface GetPostInterface
{
    #[WebQuery(method: 'GET', uri: 'https://{domain}/posts/{id}')]
    public function __invoke(string $id): array;
}
```

認証のためのヘッダーの指定などはGuzzleのClinetInterfaceをバインドして行います。

```php
$this->bind(ClientInterface::class)->toProvider(YourGuzzleClientProvicer::class);
```

インストールは`MediaQueryModule`の3つ目の引数でアサインするドメインを指定します。

```php
$module = new MediaQueryModule($mediaQueries, $sqlDir,  ['domain' => 'httpbin.org']);
```

WebQueryの時と同じようにVOを渡す事もできます。

## プロファイラー

メディアアクセスはロガーで記録されます。標準ではテストに使うメモリロガーがバインドされています。

```php
public function testAdd(): void
{
    $this->sqlQuery->exec('todo_add', $todoRun);
    $this->assertStringContainsString('query:todo_add({"id":"1","title":"run"})', (string) $this->log);
}
```

独自の[MediaQueryLoggerInterface](src/MediaQueryLoggerInterface.php)を実装して、
各メディアクエリーのベンチマークを行ったり、インジェクトしたPSRロガーでログをする事もできます。
