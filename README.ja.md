# Ray.MediaQuery

## Overview

`Ray.MediaQuery` 外部メディアのクエリーをシンプルにします。

## Motivation

* ドメイン層とインフラ層の境界をコードの中で明確に持つことができます。
* 実行オブジェクトは自動的に生成されるので、実行のための手続き的なコードを書く必要はありません。
* 利用コードは外部メディアの実態には無関係なので、後からストレージを変更することができ、並列開発やスタッビングが容易です。

## Composer install

    $ composer require ray/media-query

## Componests

* SqlQuery
* MediaQuery

2つのコンポーネントが用意されています。
1つはSQLの実行を文ではなくファイルIDで行う**SqlQuery**、もう1つはAOPを使いメソッドをSQL実行に置き換える**MediaQuery**です。

## Documentation

This package is fully documented [here](./docs/ja/index.md).

## Demo

[demo](/demo)では上記２種類のやり方で、それぞれ`user_add`、`user_item`の実行オブジェクトを作成しています。
インジェクター生成の例と合わせてご覧ください。

```php
php demo/run.php
```
