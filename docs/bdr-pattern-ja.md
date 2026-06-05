---
layout: default
title: BDR パターン実践ガイド
description: 明示的な SQL、ファクトリ、イミュータブルなドメインオブジェクトを組み合わせる Business Domain Repository Pattern の実践ガイド。
lang: ja
permalink: /bdr-pattern/ja/
---

# Business Domain Repository Pattern（BDRパターン）実践ガイド

[English]({{ '/bdr-pattern/' | relative_url }})

## はじめに

プログラマーは長い間、リレーショナル思考とオブジェクト指向思考の間の**境界**に悩まされてきました。この問題は「オブジェクト・リレーショナル・インピーダンス・ミスマッチ」と呼ばれ、リレーショナルデータベースの表形式データとオブジェクト指向プログラミングの階層的オブジェクト構造との間の根本的な非互換性を指しています。

従来のORMはSQLを抽象化して**見えなくする**ことを試みました。この抽象化により、開発者が常に感じる境界が生まれました。私たちはデータベースの力を活用しながら、オブジェクト指向の原則を維持したいのです。この2つの欲求をどう調和させるかは常に課題でした。

**BDRパターンはこの境界を溶解します。**

**BDRパターンでは、SQLとOOPが握手します**。それぞれが最も得意なことを行いながら、調和して動作します。

境界によって生じる摩擦が解消されます。もはや強制的な抽象化や、一方のパラダイムがもう一方の存在を無視する必要はありません。

## エグゼクティブサマリー

BDRパターンは、**オブジェクト指向プログラミングとSQLが調和して動作する**新しいパラダイムを提示します。「SQL基盤によるOOP自律性」を実現し、以下を可能にします：

**コア価値提案：**
- **SQLはSQLのまま**：複雑なクエリ、JOIN、ウィンドウ関数 - すべて最大性能で
- **オブジェクトはオブジェクトのまま**：豊富な振る舞いを持つ自律的なドメインモデル
- **両方の強みを活用**：オブジェクト指向設計とSQL性能の両方を達成
- **明確なテスト**：各コンポーネントを独立してテスト可能

## なぜこれが重要なのか

### 開発における一般的なシナリオ

複雑なビジネスロジックがコントローラー全体に散らばっている。一つの変更で複数のメソッドの修正が必要になり、テストには多数のモックオブジェクトが必要になります。

ORMを使用する際、以下のような特徴に遭遇します：
- N+1クエリ問題への対処が必要
- 複雑なJOINの表現における制約
- 生成されるSQLの予測困難
- パフォーマンス調整のための創造的な回避策が必要

**BDRパターンは異なるアプローチを提案します。**

SQLとOOPの両方の強みを活用し、それぞれが得意分野で輝けるようにします。

### BDRパターンのアプローチ

```php
public function showOrderDetails(string $id): Response
{
    $order = $this->orderRepo->getOrder($id);
    return $this->render('order.html.twig', ['order' => $order]);
}

// ビジネスロジックはファクトリーに
// SQLは最適化されたクエリファイルに
// テストは独立したレイヤーで
```

シンプルな構造により、保守性と可読性が向上します。

## 問題と解決策

### 従来のアプローチの問題

```php
class OrderController
{
    public function show(string $id): Response
    {
        $order = $this->orderRepo->findById($id); // 単純なデータ

        // ビジネスロジックがコントローラーに散乱 - テストの悪夢！
        $items = $this->inventoryService->checkStock($order->items);
        $tax = $this->taxCalculator->calculate($items, $order->region);
        $shipping = $this->shippingService->calculate($items, $order->region);
        $canFulfill = $this->validateOrder($items, $order->status);

        // このコントローラーのテストには6つ以上の依存関係のモックが必要！

        return $this->render('order.html.twig', compact('order', 'tax', 'shipping', 'canFulfill'));
    }
}
```

### BDRパターンソリューション

```php
class OrderController
{
    public function show(string $id): Response
    {
        // リポジトリが完全なドメインオブジェクトを返却
        $order = $this->orderRepo->getOrder($id);

        // コントローラーはレンダリングのみ - ビジネスロジックなし
        return $this->render('order.html.twig', ['order' => $order]);
    }
}
```

## オブジェクトの自律性

BDRパターンは重要なことを達成します：**SQL基盤による真のオブジェクト自律性**。

オブジェクトの自律性とSQL効率のバランスは従来困難とされていました。BDRパターンはこのバランスを実現します。ドメインオブジェクトは独自の振る舞いとデータを持つ自己完結型でありながら、その作成はSQLクエリによって効率的に支えられています。

### 読み取り専用でイミュータブルなドメインオブジェクト

**重要なことに、BDRパターンのドメインオブジェクトは読み取り専用（Read-Only）でイミュータブル（不変）です。** これらはデータベースのある時点のスナップショットを表現します。これらのオブジェクトは：

- **`save()`メソッドを持たない** - 自身を永続化しない
- **セッターを持たない** - 生成後に状態を変更できない
- **クエリ結果である** - アーキテクチャの「読み取り」側を表現

このイミュータビリティは意図的なものであり、重要な利点をもたらします：
- **デフォルトでスレッドセーフ** - 並行操作間で安全に共有可能
- **予測可能な振る舞い** - 状態が予期せず変わることがない
- **明確な意図** - クエリ（データ読み取り）とコマンド（データ変更）の分離

```php
// ドメインオブジェクトでのDIの力を活用
final readonly class UserDomainObject
{
    public function __construct(
        public string $id,
        public string $name,
        public string $role,
        // ファクトリーから注入されたサービス
        private PermissionService $permissionService,
    ) {}

    // 注入されたサービスによる動的なビジネスルール
    public function canEdit(Document $document): bool
    {
        // ORMエンティティでは不可能 - 外部サービスに依存
        // テスト環境：FakePermissionService（誰でも編集可能）
        // 本番環境：RealPermissionService（複雑な権限チェック）
        return $this->permissionService->canEdit($this, $document);
    }

    // 注意：save()、update()、セッターメソッドは存在しない
    // このオブジェクトは読み取り専用のスナップショット
}
```

BDRパターンでは、オブジェクトは単なるデータコンテナではなく、ビジネスロジックを含むドメインオブジェクトです。これらはビジネスドメインに関する質問に答えますが、データベース自体を変更することはありません。

## 実装ガイド

### 1. リポジトリインターフェースの定義

```php
interface OrderRepositoryInterface
{
    #[DbQuery('order_detail', factory: OrderDomainFactory::class)]
    public function getOrder(string $id): OrderDomainObject;

    #[DbQuery('active_orders', factory: OrderDomainFactory::class)]
    /** @return array<OrderDomainObject> */
    public function getActiveOrders(): array;
}
```

### 2. SQLクエリ（order_detail.sql）

```sql
SELECT
    o.id,
    o.customer_id,
    o.region,
    o.status,
    o.created_at,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'product_id', oi.product_id,
            'name', p.name,
            'quantity', oi.quantity,
            'price', oi.price,
            'current_stock', p.stock
        )
    ) as items
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.product_id
WHERE o.id = :id
GROUP BY o.id
```

### 3. ドメインファクトリーの実装

```php
final class OrderDomainFactory
{
    public function __construct(
        private TaxCalculator $taxCalculator,
        private ShippingService $shippingService,
        private InventoryService $inventoryService,
        private BusinessRuleEngine $ruleEngine,
    ) {}

    public function factory(
        string $id,
        string $customer_id,
        string $region,
        string $status,
        string $items_json
    ): OrderDomainObject {
        $items = json_decode($items_json, true);

        // ビジネスロジックをファクトリーに集約
        $validatedItems = $this->inventoryService->validateStock($items);
        $subtotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $items));
        $tax = $this->taxCalculator->calculate($validatedItems, $region);
        $shipping = $this->shippingService->calculate($validatedItems, $region);

        return new OrderDomainObject(
            id: $id,
            customerId: $customer_id,
            region: $region,
            status: $status,
            items: $validatedItems,
            subtotal: $subtotal,
            tax: $tax,
            shipping: $shipping,
            total: $subtotal + $tax + $shipping,
            canFulfill: count($validatedItems) === count($items) && $status === 'pending',
            insufficientStockItems: $this->getInsufficientStockItems($items, $validatedItems),
            ruleEngine: $this->ruleEngine,
        );
    }

    private function getInsufficientStockItems(array $original, array $validated): array
    {
        // 在庫不足商品を特定するビジネスロジック
        return array_filter($original, fn($item) =>
            !in_array($item['product_id'], array_column($validated, 'product_id'))
        );
    }
}
```

### 4. 豊富なドメインオブジェクト

```php
final readonly class OrderDomainObject
{
    public function __construct(
        public string $id,
        public string $customerId,
        public string $region,
        public string $status,
        public array $items,                    // 在庫検証済み商品
        public float $subtotal,
        public float $tax,                      // 地域別計算済み税額
        public float $shipping,                 // 計算済み送料
        public float $total,                    // 総合計
        public bool $canFulfill,                // 適用済みビジネスルール
        public array $insufficientStockItems,   // 在庫不足商品リスト
        // 注入されたビジネスルールエンジン - ORMでは不可能
        private BusinessRuleEngine $ruleEngine,
    ) {}

    // ドメインオブジェクトの振る舞い
    public function getDisplayTotal(): string
    {
        return '$' . number_format($this->total, 2);
    }

    public function hasInsufficientStock(): bool
    {
        return count($this->insufficientStockItems) > 0;
    }

    public function getTaxRate(): float
    {
        return $this->subtotal > 0 ? ($this->tax / $this->subtotal) * 100 : 0;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function canProcess(): bool
    {
        return $this->canFulfill && $this->isPending();
    }

    // 注入されたサービスによる動的なビジネスルール
    public function getBusinessPriority(): string
    {
        // ORMエンティティでは不可能 - 外部サービスに依存
        // テスト環境：緩い閾値（例：$100以上で高優先度）
        // 本番環境：厳しい閾値（例：$10,000以上で高優先度）
        // 繁忙期：異なる閾値
        // VIP顧客：特別ルール適用
        return $this->ruleEngine->calculatePriority($this);
    }
}
```

## 3層テスト戦略：シンプルで信頼性の高いテスト

BDRパターンの重要な利点の一つは、**テストがシンプルで信頼性が高くなる**ことです。

### 一般的なテストの課題

テストにおける一般的な課題には以下があります：
- 統合テストの実行時間が長い
- データベース状態依存によるテストの不安定性
- 複雑なモック設定
- 間欠的に失敗するテスト

**BDRパターンはより良い方法を提供します。**

### なぜテストがシンプルになるのか

各レイヤーが**独立している**ため、それぞれを個別にテストすれば、組み合わせは自然に動作します：

1. **SQLクエリ**：入力に対して正しいデータを返すか？
2. **ファクトリー**：データを正しくドメインオブジェクトに変換するか？
3. **ドメインオブジェクト**：ビジネスルールを正しく実装しているか？

これらが個別に正しければ、組み合わせは必然的に正しくなります。**論理的構造です。**

### 1. SQLレイヤーテスト

```php
class OrderQueryTest extends DatabaseTestCase
{
    public function testOrderDetailQuery(): void
    {
        // データフィクスチャーを準備
        $this->insertOrder('order-1', 'customer-1', 'tokyo', 'pending');
        $this->insertOrderItem('order-1', 'product-1', 2, 1000);

        // クエリ実行
        $result = $this->executeQuery('order_detail.sql', ['id' => 'order-1']);

        // 結果検証
        $this->assertEquals('order-1', $result[0]['id']);
        $this->assertJson($result[0]['items']);
    }
}
```

### 2. ファクトリーレイヤーテスト

```php
class OrderDomainFactoryTest extends TestCase
{
    public function testCreatesRichDomainObject(): void
    {
        // フェイク実装を使用（AIツールによる解析可能）
        $taxCalculator = new FakeTaxCalculator(['tokyo' => 0.08]);
        $shippingService = new FakeShippingService(['tokyo' => 500]);
        $inventoryService = new FakeInventoryService(['product-1' => 10]);
        $ruleEngine = new FakeBusinessRuleEngine();

        $factory = new OrderDomainFactory($taxCalculator, $shippingService, $inventoryService, $ruleEngine);

        // ファクトリーをテスト
        $order = $factory->factory(
            'order-1',
            'customer-1',
            'tokyo',
            'pending',
            '[{"product_id": "product-1", "quantity": 2, "price": 1000}]'
        );

        // ビジネスロジック結果を検証
        $this->assertEquals(2000, $order->subtotal);
        $this->assertEquals(160, $order->tax);      // 8%
        $this->assertEquals(500, $order->shipping);
        $this->assertEquals(2660, $order->total);
        $this->assertTrue($order->canFulfill);
    }
}
```

### 3. ドメインオブジェクトテスト

```php
class OrderDomainObjectTest extends TestCase
{
    public function testDomainObjectBehavior(): void
    {
        $ruleEngine = new FakeBusinessRuleEngine();
        $order = new OrderDomainObject(
            id: 'order-1',
            customerId: 'customer-1',
            region: 'tokyo',
            status: 'pending',
            items: [['product_id' => 'p1', 'quantity' => 2]],
            subtotal: 2000,
            tax: 160,
            shipping: 500,
            total: 2660,
            canFulfill: true,
            insufficientStockItems: [],
            ruleEngine: $ruleEngine,
        );

        // 振る舞いをテスト
        $this->assertEquals('$2,660.00', $order->getDisplayTotal());
        $this->assertEquals(8.0, $order->getTaxRate());
        $this->assertTrue($order->canProcess());
        $this->assertFalse($order->hasInsufficientStock());
    }
}
```

各レイヤーが独立してテストされるため、統合問題は極めて稀です。これにより、複雑で脆弱な統合テストの必要性が排除されます。

## 実用的なパターン

### ポリモーフィックドメインオブジェクト

```php
final class UserDomainFactory
{
    public function factory(string $id, string $email, string $subscription_type): UserInterface
    {
        // ビジネスルールに基づく動的オブジェクト作成
        return match ($subscription_type) {
            'free' => new FreeUser(
                id: $id,
                email: $email,
                maxProjects: 3,
                adsEnabled: true,
            ),
            'premium' => new PremiumUser(
                id: $id,
                email: $email,
                maxProjects: 100,
                prioritySupport: true,
            ),
            'enterprise' => new EnterpriseUser(
                id: $id,
                email: $email,
                dedicatedSupport: true,
                ssoEnabled: true,
            ),
        };
    }
}
```

### 外部API統合

```php
final class ProductDomainFactory
{
    public function __construct(
        private ExchangeRateService $exchangeRate,  // 外部API
        private ReviewService $reviewService,       // 外部API
    ) {}

    public function factory(string $id, string $name, float $price_usd): ProductDomainObject
    {
        // 外部サービスからのデータで充実化
        $priceJpy = $this->exchangeRate->convert($price_usd, 'USD', 'JPY');
        $reviews = $this->reviewService->getReviewSummary($id);

        return new ProductDomainObject(
            id: $id,
            name: $name,
            priceUsd: $price_usd,
            priceJpy: $priceJpy,
            reviewAverage: $reviews->average,
            reviewCount: $reviews->count,
            isPopular: $reviews->average >= 4.0 && $reviews->count >= 10,
        );
    }
}
```

### キャッシュ戦略

```php
final class CachedUserDomainFactory
{
    public function __construct(
        private CacheInterface $cache,
        private PermissionService $permissionService,
    ) {}

    public function factory(string $id, int $role_id): UserDomainObject
    {
        // 高コストな操作をキャッシュ
        $cacheKey = "permissions_role_{$role_id}";
        $permissions = $this->cache->remember($cacheKey, 3600,
            fn() => $this->permissionService->getPermissions($role_id)
        );

        return new UserDomainObject(
            id: $id,
            permissions: $permissions,
            canEdit: in_array('edit', $permissions),
            canDelete: in_array('delete', $permissions),
        );
    }
}
```

## 既存プロジェクトからの移行

### ステップ1：ビジネスロジックの特定

```php
// Before: ロジックがコントローラーに散在
class ProductController
{
    public function show($id)
    {
        $product = $this->repo->find($id);

        // このビジネスロジックを特定
        $product->finalPrice = $this->calculatePrice($product);
        $product->inStock = $this->inventory->check($product->id);
        $product->reviews = $this->reviewService->get($product->id);

        return view('product', compact('product'));
    }
}
```

### ステップ2：ドメインファクトリーの作成

```php
// After: ロジックをファクトリーに移動
final class ProductDomainFactory
{
    public function factory($id, $basePrice, $categoryId): ProductDomainObject
    {
        return new ProductDomainObject(
            id: $id,
            finalPrice: $this->calculatePrice($basePrice, $categoryId),
            inStock: $this->inventory->check($id),
            reviews: $this->reviewService->get($id),
        );
    }
}
```

### ステップ3：段階的移行

1. **新機能から始める** - 新機能をBDRパターンで実装
2. **高トラフィックエンドポイントを優先** - パフォーマンス向上の影響が大きい
3. **既存テストカバレッジを活用** - 既存テストを活用しながら移行
4. **チーム内での知識共有** - ファクトリーパターンの利点を共有

## AI時代への対応：透明性の実現

BDRパターンのもう一つの利点は、**AIツールに対して透明なコードベース**を作り出すことです。

従来のORMの複雑な抽象化レイヤーは、AIにとってブラックボックスでした：
- どのようなSQLが実行されるかが不明確
- ビジネスロジックがどこに存在するかの追跡が困難
- 暗黙の依存関係の理解が困難

BDRパターンでは、すべてが明示的です：
- **どのデータにアクセスするか**：SQLファイルで可視化
- **どう変換されるか**：ファクトリーメソッドで明確
- **どのサービスが使用されるか**：コンストラクターで明示的
- **ビジネスロジックフロー**：クエリ → ファクトリー → ドメインオブジェクトで追跡可能

```sql
-- order_detail.sql - AIが読み理解できる
SELECT
    o.id,
    o.region,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'product_id', oi.product_id,
            'quantity', oi.quantity,
            'price', oi.price
        )
    ) as items
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
WHERE o.id = :id
```

```php
// ファクトリー - AIが依存関係とロジックを完全理解
public function __construct(
    private TaxCalculator $taxCalculator,      // 明示的依存関係
    private ShippingService $shippingService,  // 明示的依存関係
) {}
```

この透明性により、AIアシスタントがコードベースを深く理解し、より正確な提案と自動化を提供できるようになります。

## まとめ

BDRパターンは**ドメイン協調の一つの形**を提示します。異なるパラダイムの橋渡しをするだけでなく、**異なるメディア間の境界を溶解**します。

SQL（宣言的、集合ベース）とOOP（命令的、オブジェクトベース）。異なる特性を持つこれらの技術をどう組み合わせるかは長年の課題でした。

**BDRパターンはこの課題への一つのアプローチを提供します。**

従来のORMがSQLを抽象化することで作り出した境界。BDRパターンはこれらを溶解し、新しい調和を作り出します。

達成される成果は：
- **コントローラーがシンプルになる** - プレゼンテーションに集中
- **ビジネスロジックが適切な場所に** - ファクトリーに配置
- **テストが明確で独立** - 各レイヤーが独立して品質を保証
- **パフォーマンス妥協なし** - SQL性能を最大化

**SQLとOOPが調和して動作します。**

BDRパターンでは、それぞれが自身の領域で優秀さを発揮しながら、共により大きなものを構築します。

<h2 id="faq">FAQ</h2>

### Q: 変更されたオブジェクトをどうやってDBに書き戻す（保存する）のですか？

**A: BDR のクエリオブジェクト自体は保存しません。** BDR パターンのオブジェクトは読み取り専用の Query モデル、つまり画面、帳票、API レスポンス、ユースケースに合わせた projection です。状態を変更する必要がある場合は、その変更を Command 側の意思決定として表します。

1. **意図に名前を付ける** - `ProcessOrder`、`DeactivateUser`、`ChangeShippingAddress`
2. **Command モデルで判断する** - ドメインの整合性を守り、成功/失敗の理由を明確にする
3. **結果を永続化する** - 必要な UPDATE、INSERT、DELETE を Command の流れで実行する

これは**CQRS（Command Query Responsibility Segregation：コマンド・クエリ責任分離）**の原則に従います：

```php
// Query側（BDRパターン）: 今の用途に必要なprojection
$order = $this->orderQuery->getOrder($id);
if ($order->canShowProcessAction()) {
    // アプリケーションは操作を提示できる。ただし最終判断はCommand側が行う。
    $this->processOrder->execute($id, new DateTimeImmutable());
}

// processOrder は必要に応じて明示的な書き込みSQLを使う：
// UPDATE orders SET status = 'processed', processed_at = :timestamp WHERE id = :id
```

この分離は意図的です：
- **Command モデル**はドメインの整合性を守り、業務上の行為を実行してよいかを判断する
- **Query モデル**は画面や帳票に合わせてデータを形作り、用途が変われば置き換えてよい
- **SQL** は projection に向いている。JOIN、集約、計算、非正規化された結果の形を自然に表せる

### Q: これはCQRSパターンですか？

**A: はい。BDR は CQRS の Query 側を表現します。** ただし、それは Read Repository と Write Repository を別の場所に置く、という話が中心ではありません。重要なのは、関心とモデルを分けることです。

出発点は単純です。読み取りと書き込みでは、最適なモデルが違います。

書き込み側には、ドメインの整合性を守るモデルが必要です。そこでは意図、振る舞い、不変条件、失敗理由を扱います。「この業務上の行為は実行してよいか？」に答えるモデルです。

読み取り側では、画面、帳票、API レスポンスのために、非正規化されたフラットなデータが欲しいことがよくあります。「今表示するにはどんな形が役に立つか？」に答えるモデルです。同じ Repository や Entity モデルで両方を満たそうとするから無理が生じます。

CQRS はしばしば、データベースを分ける、インフラを分ける、Repository の置き場所を分ける、といった物理的なアーキテクチャとして誤解されます。それらは有用な実装上の選択肢になることはありますが、本質ではありません。本質は、Command は業務の意思決定であり、Query は表示のための構造である、という分離です。

SQL はそもそもこの Query 側の性質を持っています。`SELECT` は JOIN、集約、計算を使って、必要な形へ結果を projection できます。その構造を永続的な正規ドメインモデルであるかのように扱う必要はありません。BDR では、SQL ファイルがその projection を定義し、ファクトリやドメインオブジェクトが PHP の型として表面を与えます。

Query モデルは使い捨てであってもかまいません。画面、帳票、API レスポンスが変われば、別の `SELECT` と小さな読み取りモデルを作ればよい。それは DRY 違反ではなく、CQRS の要点です。関心が違うものには、違うモデルを与えます。

### Q: ファクトリーで外部APIを呼ぶと、リスト取得時に遅くなりませんか？

**A: その通りです、適切な戦略なしでは。** これは本質的にN+1問題の変形です。以下は緩和のための戦略です：

**1. バッチリクエスト**
```php
final class ProductDomainFactory
{
    private array $priceCache = [];

    public function factory(string $id, string $name): ProductDomainObject
    {
        // 価格はファクトリー呼び出し前にバッチで取得済み
        $price = $this->priceCache[$id] ?? $this->priceService->getPrice($id);
        return new ProductDomainObject($id, $name, $price);
    }

    public function warmPriceCache(array $productIds): void
    {
        // すべての価格を1回のAPI呼び出しで取得
        $this->priceCache = $this->priceService->getPrices($productIds);
    }
}
```

**2. 遅延ロード**
```php
final readonly class ProductDomainObject
{
    public function __construct(
        private string $id,
        private PriceProvider $priceProvider,
    ) {}

    public function getCurrentPrice(): float
    {
        // 実際に必要な時だけ取得する。キャッシュはreadonlyオブジェクトではなくprovider側で管理する。
        return $this->priceProvider->getPrice($this->id);
    }
}
```

**3. 戦略的データロード**
```php
// リスト表示：高コストなデータをロードしない
#[DbQuery('product_list_simple', factory: ProductListFactory::class)]
public function getProductList(): array;

// 詳細表示：外部データを含むすべてをロード
#[DbQuery('product_detail', factory: ProductDetailFactory::class)]
public function getProduct(string $id): ProductDomainObject;
```

重要なのは、いつどのようにデータをロードするかについて**意図的であること**です。ファクトリーパターンは、この戦略を完全にコントロールする力を与えます。

## BEAR.Sunday 連携

BEAR.Sunday は、アプリケーションの操作を URI で参照できる `ResourceObject` として表す PHP アプリケーションフレームワークです。`#[Embed]` は、それらのリソース間の関係を宣言します。

この境界は BDR と相性が良いです。Repository は **何を**問い合わせるかを宣言したまま、独立したリソースリクエストを **いつ** **どう**実行するかは BEAR.Sunday と BEAR.Async がアプリケーション境界で決めます。Repository interface と SQL file は変わりません。

- [BDR + BEAR.Async: 並列 SQL レシピ](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BEAR_ASYNC_RECIPE-ja.md) — 各リポジトリ呼び出しを `ResourceObject` で包むと、`#[Embed]` がアプリケーション境界でそれらを並列実行する。

## 参考文献

- [Object-Relational Mapping is the Vietnam of Computer Science](https://blog.codinghorror.com/object-relational-mapping-is-the-vietnam-of-computer-science/) - Jeff Atwood (2006)
