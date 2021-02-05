# Pages

ページャーオブジェクトは、配列アクセスでページを取得したりcount()で全体の件数を取得することができます。
SQLは遅延実行されます。

```php
echo count($pages); // countした時にカウントクエリーが生成され実行されます。
```

```
$page = $pages[2]; // 配列アクアセスをした時にそのページのクエリーが行われます。

// $page->data // スライスされたページデータ
// $page->current;
// $page->total
// $page->hasNext
// $page->hasPrevious
// $page->maxPerPage;
// (string) $page // リンクのためのpager html
