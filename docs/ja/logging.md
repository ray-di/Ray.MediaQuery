# Logger

メディアアクセスはロガーで記録されます。


##  テスト

標準のメディアクレリーロガーはメモリーに保存するだですが、以下のようにメディアアクセスの実行と引数をテストする事ができます。

```php
public function testExec(): void
{
    $this->sqlQuery->exec('todo_add', $this->insertData);
    $this->assertStringContainsString('query:todo_add({"id":"1","title":"run"})', (string) $this->log);
}
```

## 拡張

`src/MediaQueryLogger`を拡張して独自のロガー/プロファイラーを作成する事ができます。
