# SqlQuery

`SqlQuery`はSQLの代わりにSQLファイルのIDを指定してSQLを実行します。

## Getting Started

SQLファイルを`$sqlDir/user_add.sql`保存します。

```sql
INSERT INTO user (id, name) VALUES (:id, :name);
```

exec()で`user_add.sql`のSQLが実行されます。

```php
$sqlQuery = SqlQueryFactory::getInstance($sqlDir, 'sqlite::memory:');
$sqlQuery->exec('user_add', ['id' => '1', 'name' => 'ray');
```

1つのファイルに複数のSQLを記述できます。`;`で区切ってください。

## Get* Method

SELECT結果を取得するためには取得する結果に応じた`get*`を使います。

```php
$sqlQuery->getRow($queryId, $params); // 結果が単数行
$sqlQuery->getRowList($queryId, $params); // 結果が複数行
$statement = $sqlQuery->getStatement(); // PDO Statementを取得
$pages = $sqlQuery->getPages(); // ページャーを取得
```
ページャー([Pages](Pages.md))は、配列アクセスでページを取得したりcount()で全体の件数を取得することができます。
