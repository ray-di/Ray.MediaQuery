# Ray.MediaQuery

## Overview

`Ray.MediaQuery` 外部メディアのクエリーのインターフェイスを、クエリー実行オブジェクトに変えインジェクトします。

## Motivation

* ドメイン層とインフラ層の境界をコードの中で明確に持つことができます。
* 実行オブジェクトは自動的に生成されるので、実行のための手続き的なコードを書く必要はありません。
* 利用コードは外部メディアの実態には無関係なので、後からストレージを変更することができ、並列開発やスタッビングが容易です。

## Composer install

    $ composer require ray/media-query

## Usage

アプリケーションがメディアアクセスするインターフェイスを定義します。
この時メソッドに`DbQuery`の属性をつけて、SQLのIDを指定します。

```php
interface TodoAddInterface
{
    #[DbQuery('user_add')]
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

上記、クエリーインターフェイスクラスを指定してモジュールをインストールします。

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

SQL実行オブジェクトは、クエリーIDで指定されたSQLファイルを指定された引数でバインドして実行します。
例えば、`TodoItem::__invoke()`は`todo_item.sql`SQL文に`['id => $id]`をバインドして実行した結果を返します。

* `$sqlDir/`ディレクトリにそれぞれのSQLを用意します。クラスが`TodoAdd`なら`$sqlDir/todo_add.sql`です。
* SQL実行が返すの単一行なら`item`、複数行なら`list`のpostfixを付けます。
* SQLファイルには複数のSQL文が記述できます。最後の行のSELECTが実行結果として返ります。

## Pagination

`#[Pager]`アノテーションで、データベースのSELECTクエリーをページングする事ができます。

```php
interface TodoList
{
    #[DbQuery, Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): Pages
    {
    }
}
```

ページ番号で配列アクセスするとその時点でDBクエリーが行われページオブジェクトが取得できます。

```php
$pages = ($todoList)();
$cnt = count($page); // カウントSQLが生成されクエリーが行われます。
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

データの編集など、実装クラスを用意する場合は`SqlQuery`を使います。
`SqlQuery`はSQLファイルのIDを指定してSQLを実行します。

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

動的なクエリーはAura.Sqlのクエリービルダーをお使いください。