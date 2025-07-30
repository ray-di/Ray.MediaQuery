# Ray.MediaQuery

## データベースアクセスマッピングフレームワーク
[![codecov](https://codecov.io/gh/ray-di/Ray.MediaQuery/branch/1.x/graph/badge.svg?token=QBOPCUPJQV)](https://codecov.io/gh/ray-di/Ray.MediaQuery)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.MediaQuery/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.MediaQuery)
[![Continuous Integration](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml)

[English](./README.md)

## 概要

`Ray.MediaQuery`はインターフェイスベースのクエリー定義によるデータベースクエリー抽象化を提供します。

## モチベーション

* このフレームワークはインターフェイスベースのクエリー定義によるデータベースクエリー抽象化を提供します。
* 実行オブジェクトは自動生成されるため、実行のための手続き的なコードを書く必要がありません。
* 使用コードは外部メディアの実際の状態に関係ないため、ストレージを後から変更できます。並列開発とスタブ作成が容易です。

## インストール

    $ composer require ray/media-query

Web APIクエリーの場合は、別パッケージをインストールしてください：

    $ composer require ray/web-query

> **注意:** このパッケージはPHP 8.1+が必要で、PHP 8 Attributesを使用します。レガシーアノテーション（`@DbQuery`）は非推奨です。Rectorを使用してattributes（`#[DbQuery]`）に移行してください。

## はじめに

データベースアクセス用のインターフェイスを定義します。

### DB

`DbQuery`属性でSQLのIDを指定します。

```php
interface TodoAddInterface
{
    #[DbQuery('user_add')]
    public function add(string $id, string $title): void;
}
```

### モジュール

MediaQueryModuleは、`DbQueryConfig`設定でSQLの実行をインターフェイスに束縛します。

```php
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;

protected function configure(): void
{
    $this->install(
        new MediaQueryModule(
            Queries::fromDir('/path/to/queryInterface'),
            new DbQueryConfig('/path/to/sql')
        ),
    );
    $this->install(new AuraSqlModule('mysql:host=localhost;dbname=test', 'username', 'password'));
}
```

注) MediaQueryModuleはAuraSqlModuleのインストールが必要です。

### リクエストオブジェクトインジェクション

実装クラスをコーディングすることなく、インターフェイスからクエリー実行オブジェクトが生成されインジェクトされます。

```php
class Todo
{
    public function __construct(
        private TodoAddInterface $todoAdd
    ) {}

    public function add(string $id, string $title): void
    {
        $this->todoAdd->add($id, $title);
    }
}
```

### DbQuery

メソッドをコールするとIDで指定されたSQLをメソッドの引数でバインドして実行します。
例えばIDが`todo_item`の指定では`todo_item.sql`SQL文を`['id => $id]`でバインドして実行します。

```php
interface TodoItemInterface
{
    #[DbQuery('todo_item', type: 'row')]
    public function item(string $id): array;

    #[DbQuery('todo_list')]
    /** @return array<Todo> */
    public function list(string $id): array;
}
```

* 結果が `row`(`array<string, scalar>`)の場合は`type:'row'`を指定します。`row_list`(`array<int, array<string, scalar>>`)にはtype指定は不要です。
* SQLファイルには複数のSQL文が記述できます。その場合には最後の行のSELECTが戻り値になります。

#### エンティティ

メソッドの戻り値をエンティティクラスにするとSQL実行結果がハイドレートされます。

```php
interface TodoItemInterface
{
    #[DbQuery('todo_item')]
    public function item(string $id): Todo;

    #[DbQuery('todo_list')]
    /** @return array<Todo> */
    public function list(string $id): array;
}
```

```php
final class Todo
{
    public readonly string $id;
    public readonly string $title;
}
```

プロパティをキャメルケースに変換する場合には`StringCase`ユーティリティを使います。

```php
use Ray\MediaQuery\StringCase;

// データベースのsnake_caseをcamelCaseに変換
$camelCase = StringCase::camel('user_name'); // 'userName'
$snakeCase = StringCase::snake('userName');  // 'user_name'
```

エンティティにコンストラクタがあると、フェッチしたデータでコールされます。

```php
final class Todo
{
    public function __construct(
        public readonly string $id,
        public readonly string $title
    ) {}
}
```

### ページネーション

DBの場合、`#[Pager]`属性でSELECTクエリーをページングする事ができます。

```php
use Ray\MediaQuery\Pages;

interface TodoList
{
    #[DbQuery('todo_list'), Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): Pages;
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

## デモ

テストとデモを行うためには以下のようにします。

```
$ git clone https://github.com/ray-di/Ray.MediaQuery.git
$ cd Ray.MediaQuery
$ composer tests
$ php demo/run.php
```