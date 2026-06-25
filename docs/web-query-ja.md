---
layout: default
title: Ray.WebQuery — Web レスポンスマッピング
description: "Ray.WebQuery のマニュアル。#[WebQuery] で HTTP API レスポンスを、#[DbQuery] と同じファクトリと PostFetch の仕組みで型付き・イミュータブルなドメインオブジェクトにマッピングします。"
lang: ja
permalink: /web-query/ja/
---

# Ray.WebQuery — Web レスポンスマッピング

[English]({{ '/web-query/' | relative_url }})

[Ray.WebQuery](https://github.com/ray-di/Ray.WebQuery) は [BDR パターン]({{ '/bdr-pattern/ja/' | relative_url }}) を HTTP に持ち込みます。API 呼び出しを `#[WebQuery]` を付けたインターフェースメソッドとして宣言すると、レスポンスが型付き・イミュータブルなドメインオブジェクトにマッピングされます。`#[DbQuery]` が SQL に対して提供するファクトリと `PostFetch` の仕組みを、そのまま HTTP レスポンスに適用したものです。

`ray/media-query` 上に構築された別パッケージで、コア基盤（パラメータ注入、ロギングなど）は `ray/media-query` が提供します。同じ `Ray\MediaQuery` 名前空間に属するため、概念は[マニュアル]({{ '/reference/' | relative_url }})からそのまま引き継がれます。

## インストール

```bash
composer require ray/web-query
```

## インターフェースの定義

各メソッドに `#[WebQuery]` を付け、クエリ ID を与えます。

```php
use Ray\MediaQuery\Annotation\WebQuery;

interface UserApiInterface
{
    #[WebQuery('user_item')]
    public function get(string $id): array;
}
```

## 設定ファイル

`web_query.json` で、各クエリ ID を HTTP メソッドと URI テンプレートに対応づけます。

```json
{
  "webQuery": [
    {"id": "user_item", "method": "GET", "path": "https://{domain}/users/{id}"}
  ]
}
```

## モジュールの導入

`MediaQueryWebModule` を `MediaQueryBaseModule` に install し、インターフェースは `Queries::fromClasses()` で登録します。

```php
use Ray\Di\Injector;
use Ray\MediaQuery\MediaQueryBaseModule;
use Ray\MediaQuery\MediaQueryWebModule;
use Ray\MediaQuery\Queries;
use Ray\MediaQuery\WebQueryConfig;

$queries = Queries::fromClasses([UserApiInterface::class]);
$config = new WebQueryConfig('web_query.json', ['domain' => 'api.example.com']);

$module = new MediaQueryBaseModule($queries);
$module->install(new MediaQueryWebModule($config));

$api = (new Injector($module))->getInstance(UserApiInterface::class);
$user = $api->get('123'); // GET https://api.example.com/users/123
```

URI テンプレート変数は、メソッド引数と `WebQueryConfig` に渡した bindings から埋められます。ここでは `{domain}` が bindings から、`{id}` が `get()` の引数から展開されます。

## レスポンス型

ファクトリもエンティティも使わない場合、メソッドの戻り値型が、生の HTTP レスポンスの扱い方を決めます。

| 戻り値型                  | 結果                          |
|---------------------------|-------------------------------|
| `array`                   | JSON ボディをデコードした配列 |
| `string`                  | 生のレスポンスボディ          |
| PSR-7 `MessageInterface`  | HTTP メッセージオブジェクト   |

## レスポンスをドメインオブジェクトにマッピングする

生配列の代わりに、型付き・イミュータブルなドメインオブジェクトを返せます。`#[WebQuery]` に `factory`（および `type`）を与えます。これは `#[DbQuery]` が SQL に提供するのと同じファクトリの仕組みを、HTTP レスポンスに適用したものです。

```php
use Ray\MediaQuery\Annotation\WebQuery;

interface ProductApiInterface
{
    #[WebQuery('product_item', type: 'row', factory: ProductFactory::class)]
    public function get(string $id): Product;

    #[WebQuery('product_list', factory: ProductFactory::class)]
    /** @return array<Product> */
    public function list(string $status): array;
}
```

ファクトリは DI インジェクターで解決されるため、ドメインサービスに依存し、オブジェクト構築時に業務ロジックを適用できます。

```php
final class ProductFactory
{
    public function __construct(
        private TaxCalculator $tax,
    ) {
    }

    public function factory(string $name, int $price): Product
    {
        return new Product($name, $this->tax->applyTax($price));
    }
}

final class Product
{
    public function __construct(
        public readonly string $name,
        public readonly int $price,
    ) {
    }
}
```

デコードされた JSON は、ファクトリメソッドに **名前付き引数** として渡されます。各 JSON キーは名前でパラメータに一致づけられ、未知のキーは無視され、必須引数が欠落している場合は `MissingResponseKeyException` が投げられます（media-query の位置ベースの `PDO::FETCH_FUNC` バインディングの Web 版です）。ファクトリメソッド名はデフォルトで `factory` です。

### 単一オブジェクトとリスト: `type`

`type` は、単一オブジェクトとリストのどちらを生成するかを選びます。

| `type`       | JSON レスポンス        | 結果            |
|--------------|------------------------|-----------------|
| `'row'`      | オブジェクト `{...}`   | 単一オブジェクト |
| `'row_list'` | 配列 `[{...}, {...}]`  | `array<object>` |

`type` のデフォルトは `'row_list'` です。`'row'` のメソッドでレスポンスがリストの場合は先頭要素を取り、`'row_list'` のメソッドでレスポンスが単一オブジェクトの場合は 1 要素の配列に包みます。

## ファクトリなしの Entity Hydration

ファクトリ **なし** で、エンティティへ直接マッピングすることもできます。戻り値型（または `@return array<Entity>` の docblock）がクラスの場合、各レスポンス行は同じ名前付き引数バインディングでエンティティのコンストラクタを通して hydrate されます。

```php
#[WebQuery('product_item', type: 'row')]
public function get(string $id): Product; // Product::__construct で構築
```

コンストラクタを持たないクラスは `EntityWithoutConstructorException`、解決できないクラスは `InvalidWebEntityException` を投げます。

## PostFetch による結果の合成

取得したオブジェクトを別の型（合計、メタデータなど）にまとめたい場合は、戻り値型に `PostFetchInterface` を実装させます。静的メソッド `fromContext()` が fetch 結果を受け取り、最終的なオブジェクトを返します。ファクトリの後に実行され、設計上、依存を持ちません。

media-query の `PostQueryInterface` の Web 版です。Web 呼び出しは単一の fetch であり、複数ステートメントにまたがるクエリコンテキストがないため、*PostFetch* と名付けられています。

```php
use Ray\MediaQuery\PostFetchContext;
use Ray\MediaQuery\PostFetchInterface;

final class ProductList implements PostFetchInterface
{
    /** @param array<Product> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
    ) {
    }

    public static function fromContext(PostFetchContext $context): static
    {
        /** @var array<Product> $items */
        $items = is_array($context->result) ? $context->result : [];

        return new self($items, count($items));
    }
}
```

```php
#[WebQuery('product_list', factory: ProductFactory::class)]
public function listAggregate(string $status): ProductList;
```

`PostFetchContext` は fetch の文脈を公開します。

| プロパティ  | 型                      | 説明                                              |
|-------------|-------------------------|---------------------------------------------------|
| `$result`   | `mixed`                 | マッピング済みの fetch 結果（オブジェクトまたはリスト） |
| `$query`    | `array<string, string>` | 元のメソッド引数                                  |
| `$webQuery` | `WebQuery`              | `#[WebQuery]` アノテーション（`id`, `type`, `factory`） |

## エラーハンドリング

例外はすべて `Ray\MediaQuery\Exception` 名前空間にあります。

| 例外                                | 発生条件                                                                            |
|-------------------------------------|-------------------------------------------------------------------------------------|
| `MissingResponseKeyException`       | 必須のコンストラクタ/ファクトリ引数がレスポンスに存在しない                          |
| `InvalidWebFactoryException`        | `factory:` が呼び出し可能な静的メソッドでも DI 構築可能なクラスでもない（未定義または非 public） |
| `InvalidWebEntityException`         | 戻り値型のエンティティクラスが見つからない                                          |
| `EntityWithoutConstructorException` | コンストラクタを持たないクラスに対して Entity Hydration が要求された                 |

## 後方互換性

`factory:` がない場合、既存の生レスポンスの経路は変わりません。戻り値型が `array` なら JSON デコード済みのボディ、`string` なら生のボディ、PSR-7 `MessageInterface` なら HTTP メッセージが返ります。

## 関連項目

- [BDR パターン実践ガイド]({{ '/bdr-pattern/ja/' | relative_url }}) — ファクトリとイミュータブルなドメインオブジェクトの背後にある設計思想。
- [Ray.MediaQuery マニュアル]({{ '/reference/' | relative_url }}) — SQL 版（`#[DbQuery]`）、パラメータ処理、ページネーション。
- [GitHub の Ray.WebQuery](https://github.com/ray-di/Ray.WebQuery)
