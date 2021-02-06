# Ray.MediaQuery

## Overview

`Ray.MediaQuery` 外部メディアのクエリーのインターフェイスを実行オブジェクトに変えインジェクトします。

## Motivation

* ドメイン層とインフラ層の境界をコードの中で明確に持つことができます。
* 実行オブジェクトは自動的に生成されるので、実行のための手続き的なコードを書く必要はありません。
* 利用コードは外部メディアの実態には無関係なので、後からストレージを変更することができ、並列開発やスタッビングが容易です。

## Composer install

    $ composer require ray/media-query 1.x-dev

## Usage

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

クエリーインターフェイスを指定して、モジュールをインストールします。

```php
protected function configure(): void
{
    $mediaQueries = [
        TodoAddInterface::class,
        TodoItemInterface::class,
    ];
    $this->install(new MediaQueryModule('sqlite::memory:', $sqlDir, $mediaQueries));
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
* SQL実行が返すの単一行なら`item`、複数行なら`list`のpostfixを付けます。
* SQLファイルには複数のSQL文が記述できます。最後の行のSELECTが実行結果になります。

## Pagination

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

## Get* Method

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
