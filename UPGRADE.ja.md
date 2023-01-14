# Upgrade Guide

破壊的変更が行われたv0.xからv1.0への移行について説明します。
なお、セマンティックバージョンに従いv1.0からは破壊的変更は行われません。

## from v0.7

### エンティティ

`entity`アトリビュートが無効になりました。使用するとエラーになります。

ハイドレート（戻り値にエンティティを使用）する場合は`entity`と`type`のアトリビュートを取り除きます。

```diff
interface TodoInterface
{
-   #[DbQuery('todo_item', entity: Todo::class, type:'row')]
+   #[DbQuery('todo_item')]
    public function item(string $id): Todo;

-   #[DbQuery('todo_list', entity: Todo::class)]
+   #[DbQuery('todo_list')]
+   /** @return array<Todo>> */ 
    public function list(): array;

```

ページングの時はジェネリクスで指定します。戻り値をハイドレートするときは廃止された`entity`に変わってこの指定が必須になりました。

```diff
interface TodoPagerInterface
{
-   #[DbQuery('todo_list'), Pager(perPage: 10, template: '/{?page}'), entity: Todo:class]
+   #[DbQuery('todo_list'), Pager(perPage: 10, template: '/{?page}')]
+   /**　@return Pages<Todo>　*/
    public function __invoke(): Pages;
}
```

### array

戻り値がarrayで`row`の形式で値が戻る時のみ`type: 'row'`の指定が必要です。これはarrayだけではrow (array<string>)なのかrow_list (array<array<string>>)なのか判別がつかないからです。エンティティにハイドレートしない場合は従来と変更がありません。

```php
interface TodoInterface
{
    #[DbQuery('todo_item', type:'row')]
    public function item(string $id): array;
  
    #[DbQuery('todo_list')]
    public function list(): array;
}
```

## from v0.6

`item`や`list`などの接尾語の指定は無効になります。その他はv0.7からの移行と同じです。
