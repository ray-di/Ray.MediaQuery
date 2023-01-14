## UPGRADE GUIDE

This section describes the transition from v0.x to v1.0, which has undergone a destructive change.
Note that no destructive changes will be made from v1.0 according to the semantic version.

## from v0.7

### エンティティ

The `entity` attribute is no longer valid. Use of it will result in an error.

Remove the `entity` and `type` attributes if you want to hydrate (use entities in the return value).

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

The `type: 'row'` is required only when the return value is an array and the value is returned in the form `row`. This is because it is not possible to determine whether the return value is a row (array<string>) or row_list (array<array<string>>) from an array alone. If the entity is not hydrated, there is no change from the previous case.

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

The specification of suffixes such as `item` and `list` will be disabled. Otherwise, the transition is the same as from v0.7.
