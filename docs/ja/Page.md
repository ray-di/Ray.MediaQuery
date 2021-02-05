# Pagination

ページリストオブジェクトは、配列アクセスでページを取得したりcount()で全体の件数を取得することができます。

```php
$pages = $sqlQuery->getPages($sqlId, $params, $perPage, $queryTemplate = '/{?page}'); // ページャーを取得

$pages = ($todoList)();
echo count($pages); // countした時にカウントクエリーが生成され実行されます。
$page = $pages[2]; // 配列アクアセスをした時にそのページのクエリーが行われます。
// $page->data // sliced data
// $page->current;
// $page->total
// $page->hasNext
// $page->hasPrevious
// $page->maxPerPage;
// (string) $page // リンクのためのpager html

