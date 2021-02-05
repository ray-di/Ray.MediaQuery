# Ray.MediaQuery

## Overview

`Ray.MediaQuery` makes a query to an external media such as a database or Web API with a function object to be injected.

## Motivation

* You can have a clear boundary between domain layer (usage code) and infrastructure layer (injected function) in code.
* Execution objects are generated automatically so you do not need to write procedural code for execution.
* Since usage codes are indifferent to the actual state of external media, storage can be changed later. Easy parallel development and stabbing.

## Composer install

    $ composer require ray/media-query

## Componests

* [SqlQuery](docs/ja/SqlQuery.md)
* [MediaQuery](docs/ja/MediaQuery.ja.md)

2つのコンポーネントが用意されています。
1つはSQLの実行を文ではなくファイルIDで行うSqlQuery、もう1つはAOPを使いメソッドをSQL実行に置き換えるMediaQueryです。

## Demo

[demo](/demo)では上記２種類のやり方で、それぞれ`user_add`、`user_item`の実行オブジェクトを作成しています。
インジェクター生成の例と合わせてご覧ください。

```php
php demo/run.php
```
