# Business Domain Repository Pattern (BDR Pattern) - Practical Guide

## Prologue: The End of a Long War

Programmers have long suffered from the **uncomfortable boundary** between relational and object-oriented thinking.

Traditional ORMs tried to make SQL invisible, **pretending it didn't exist**. This forced pretense created an artificial boundary that developers have always felt uncomfortable with - the constant tension between wanting to leverage database power while maintaining object-oriented principles. It was like being torn between two worlds.

**BDR Pattern completely dissolves this boundary.**

Instead of SQL and OOP being adversaries, **in BDR Pattern, SQL and OOP shake hands**. Each does what it does best while working in harmony.

**The discomfort is gone.** No more forced abstractions, no more pretending one paradigm doesn't exist.

## Executive Summary

BDR Pattern represents a paradigm shift where **Object-Oriented Programming and SQL become allies instead of adversaries**. It achieves what was previously thought impossible: "OOP autonomy with SQL foundation," enabling:

**Core Value Proposition:**
- **SQL stays SQL**: Complex queries, JOINs, window functions - all at maximum performance
- **Objects stay objects**: Autonomous, behavior-rich domain models with proper encapsulation
- **The impossible becomes possible**: True object-oriented design powered by raw SQL performance
- **Surgical testing**: Each component tested in isolation with crystal-clear boundaries

## Why This Matters: Liberation from Developer Agony

### You've Experienced This Before

Monday morning, you're staring at a controller with scattered business logic. One change requires modifying six different controller methods. Writing tests requires setting up more than 10 mock objects.

ORMs promised to be "object-oriented," but in reality:
- Plagued by N+1 query problems
- Can't write complex JOINs (or they're inefficient)
- Generated SQL is unpredictable
- Performance tuning is difficult

**We've been trying to solve the wrong problem all along.**

The problem isn't the difference between SQL and OOP. The problem is that **one has been trying to dominate the other**.

### Daily Life with BDR Pattern

```php
// Monday morning, same requirements
public function showOrderDetails(string $id): Response
{
    $order = $this->orderRepo->getOrder($id);
    return $this->render('order.html.twig', ['order' => $order]);
}

// That's it. Really.
// Business logic is in factories
// SQL is in optimized query files
// Tests are independent at each layer
```

**It's time to reclaim your dignity as a developer.**

## Problem and Solution

### Traditional Approach Problems

```php
class OrderController
{
    public function show(string $id): Response
    {
        $order = $this->orderRepo->findById($id); // Simple data
        
        // Business logic scattered in controller - testing nightmare!
        // Call external services (PermissionService, VerificationService, PaymentGateway, etc.)
        // Apply complex business rules based on multiple service results
        // Transform and enrich data for presentation
        // Handle conditional logic for user types, subscription levels, etc.
        // Testing this controller requires mocking 6+ dependencies!
        
        return $this->render('order.html.twig', compact('order', 'tax', 'shipping', 'canFulfill'));
    }
}
```

### BDR Pattern Solution

```php
class OrderController
{
    public function show(string $id): Response
    {
        // Repository returns complete domain object
        $order = $this->orderRepo->getOrder($id);
        
        // Controller only renders - no business logic
        return $this->render('order.html.twig', ['order' => $order]);
    }
}
```

## Implementation Philosophy: Object Autonomy

The most revolutionary achievement of BDR Pattern is **achieving true object autonomy while using SQL as the foundation**.

Previously, object autonomy and SQL efficiency were considered mutually exclusive. But BDR Pattern **makes the impossible possible**. Domain objects are self-contained with their own behavior and data, yet their creation is powered by the full strength of SQL queries.

```php
// This isn't just a data holder - it's an autonomous entity
final readonly class OrderDomainObject
{
    public function __construct(
        public string $id,
        public array $items,
        public float $subtotal,
        public float $tax,
        public float $shipping,
        public float $total,
        public bool $canFulfill,
    ) {}
    
    // Object knows its own destiny
    public function canProcess(): bool
    {
        return $this->canFulfill && $this->isPending();
    }
    
    // Object understands its own state
    public function requiresManagerApproval(): bool
    {
        return $this->total > 1000000;
    }
    
    // Object has its own behavior
    public function getBusinessPriority(): string
    {
        return match(true) {
            $this->total > 5000000 => 'critical',
            $this->total > 1000000 => 'high',
            $this->total > 100000 => 'medium',
            default => 'normal'
        };
    }
}
```

**This is the essence of BDR Pattern** - objects aren't just data containers, but autonomous entities with business domain knowledge.

## Implementation Guide

### 1. Repository Interface Definition

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

### 2. SQL Query (order_detail.sql)

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

### 3. Domain Factory Implementation

```php
final class OrderDomainFactory
{
    public function __construct(
        private TaxCalculator $taxCalculator,
        private ShippingService $shippingService,
        private InventoryService $inventoryService,
    ) {}
    
    public function factory(
        string $id,
        string $customer_id,
        string $region,
        string $status,
        string $items_json
    ): OrderDomainObject {
        $items = json_decode($items_json, true);
        
        // Centralize business logic in factory
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
        );
    }
    
    private function getInsufficientStockItems(array $original, array $validated): array
    {
        // Business logic to identify insufficient stock items
        return array_filter($original, fn($item) => 
            !in_array($item['product_id'], array_column($validated, 'product_id'))
        );
    }
}
```

### 4. Rich Domain Object

```php
final readonly class OrderDomainObject
{
    public function __construct(
        public string $id,
        public string $customerId,
        public string $region,
        public string $status,
        public array $items,                    // Stock-validated items
        public float $subtotal,
        public float $tax,                      // Region-calculated
        public float $shipping,                 // Calculated shipping
        public float $total,                    // All-inclusive total
        public bool $canFulfill,                // Business rules applied
        public array $insufficientStockItems,   // Insufficient stock items list
    ) {}
    
    // Domain object behavior
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
}
```

## AI Era Adaptation: Ultimate Transparency

Another innovation of BDR Pattern is creating **codebases that are completely transparent to AI tools**.

Traditional ORM's complex abstraction layers were black boxes for AI:
- Unclear what SQL would be executed
- Difficult to track where business logic resides
- Implicit dependencies hard to understand

In BDR Pattern, everything is explicit:
- **What data is accessed**: Visible in SQL files
- **How it's transformed**: Clear in factory methods
- **What services are used**: Explicit in constructors
- **Business logic flow**: Traceable from query → factory → domain object

```sql
-- order_detail.sql - AI can read and understand this
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
// Factory - AI fully grasps dependencies and logic
public function __construct(
    private TaxCalculator $taxCalculator,      // Explicit dependency
    private ShippingService $shippingService,  // Explicit dependency
) {}
```

**This isn't just improved readability.** AI assistants can deeply understand your codebase and provide more accurate suggestions and automation.

## 3-Layer Testing Strategy: Liberation from Integration Test Hell

One of BDR Pattern's most revolutionary benefits is that **complex integration tests become almost unnecessary**.

### Traditional Testing Nightmare

Everyone has experienced:
- Integration tests taking 30 minutes to run
- Unstable tests dependent on database state
- Mock setup alone exceeding 100 lines
- Mysterious "sometimes failing" tests

**BDR Pattern ends this nightmare.**

### Why Integration Tests Become Unnecessary

Each layer is **completely independent**, so testing each individually means the combination will obviously work:

1. **SQL Query**: Does it return correct data for inputs?
2. **Factory**: Does it correctly transform data into domain objects?
3. **Domain Object**: Does it correctly implement business rules?

If these are individually correct, the combination is necessarily correct. **Mathematically obvious.**

### 1. SQL Layer Tests

```php
class OrderQueryTest extends DatabaseTestCase
{
    public function testOrderDetailQuery(): void
    {
        // Prepare data fixtures
        $this->insertOrder('order-1', 'customer-1', 'tokyo', 'pending');
        $this->insertOrderItem('order-1', 'product-1', 2, 1000);
        
        // Execute query
        $result = $this->executeQuery('order_detail.sql', ['id' => 'order-1']);
        
        // Verify results
        $this->assertEquals('order-1', $result[0]['id']);
        $this->assertJson($result[0]['items']);
    }
}
```

### 2. Factory Layer Tests

```php
class OrderDomainFactoryTest extends TestCase
{
    public function testCreatesRichDomainObject(): void
    {
        // Use fake implementations (AI tools can analyze)
        $taxCalculator = new FakeTaxCalculator(['tokyo' => 0.08]);
        $shippingService = new FakeShippingService(['tokyo' => 500]);
        $inventoryService = new FakeInventoryService(['product-1' => 10]);
        
        $factory = new OrderDomainFactory($taxCalculator, $shippingService, $inventoryService);
        
        // Test factory
        $order = $factory->factory(
            'order-1',
            'customer-1',
            'tokyo',
            'pending',
            '[{"product_id": "product-1", "quantity": 2, "price": 1000}]'
        );
        
        // Verify business logic results
        $this->assertEquals(2000, $order->subtotal);
        $this->assertEquals(160, $order->tax);      // 8%
        $this->assertEquals(500, $order->shipping);
        $this->assertEquals(2660, $order->total);
        $this->assertTrue($order->canFulfill);
    }
}
```

### 3. Domain Object Tests

```php
class OrderDomainObjectTest extends TestCase
{
    public function testDomainObjectBehavior(): void
    {
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
            insufficientStockItems: []
        );
        
        // Test behavior
        $this->assertEquals('$2,660.00', $order->getDisplayTotal());
        $this->assertEquals(8.0, $order->getTaxRate());
        $this->assertTrue($order->canProcess());
        $this->assertFalse($order->hasInsufficientStock());
    }
}
```

**Important:** Since each layer is independently tested, integration issues are extremely rare. This eliminates the need to write complex, brittle integration tests.

## Practical Pattern Collection

### Polymorphic Domain Objects

```php
final class UserDomainFactory
{
    public function factory(string $id, string $email, string $subscription_type): UserInterface
    {
        // Dynamic object creation based on business rules
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

### External API Integration

```php
final class ProductDomainFactory
{
    public function __construct(
        private ExchangeRateService $exchangeRate,  // External API
        private ReviewService $reviewService,       // External API
    ) {}
    
    public function factory(string $id, string $name, float $price_usd): ProductDomainObject
    {
        // Enrich with external service data
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

### Caching Strategy

```php
final class CachedUserDomainFactory
{
    public function __construct(
        private CacheInterface $cache,
        private PermissionService $permissionService,
    ) {}
    
    public function factory(string $id, int $role_id): UserDomainObject
    {
        // Cache expensive operations
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

## Migration from Existing Projects

### Step 1: Identify Business Logic

```php
// Before: Identify logic scattered in controllers
class ProductController
{
    public function show($id)
    {
        $product = $this->repo->find($id);
        
        // Identify these business logic pieces
        $product->finalPrice = $this->calculatePrice($product);
        $product->inStock = $this->inventory->check($product->id);
        $product->reviews = $this->reviewService->get($product->id);
        
        return view('product', compact('product'));
    }
}
```

### Step 2: Create Domain Factory

```php
// After: Move logic to factory
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

### Step 3: Gradual Migration

1. **Start with new features** - Implement new functionality with BDR Pattern
2. **Prioritize high-traffic endpoints** - Greatest performance improvement impact
3. **Leverage test coverage** - Maintain existing tests while migrating
4. **Share knowledge with team** - Communicate factory pattern benefits

## Conclusion: Dawn of a New Era

BDR Pattern represents **domain collaboration at its finest**. It doesn't just bridge different paradigms - it **melts the boundaries between different media**.

SQL (declarative, set-based) and OOP (imperative, object-based) were once considered fundamentally incompatible. Programmers have always felt uneasiness at this boundary - forced to choose sides or live with awkward compromises.

**In BDR Pattern, this discomfort completely disappears.**

Traditional ORMs tried to make SQL **invisible, pretending it didn't exist**. The artificial boundaries this created. The cognitive dissonance it forced on programmers. BDR Pattern dissolves all of this.

The result:
- **Controllers become astonishingly simple** - purely focused on presentation
- **Business logic finds its natural home** - factories as proper residence
- **Testing becomes surgical precision** - each layer independently ensures quality
- **Performance without compromise** - unleashing SQL's full power

**The long war is over.**

SQL and OOP aren't enemies. They're collaborators, each excelling in their own domain while building something greater together.

**In BDR Pattern, the impossible becomes possible.**

## References

- [ORM is the Vietnam of Computer Science](https://blog.codinghorror.com/object-relational-mapping-is-the-vietnam-of-computer-science/) - Jeff Atwood's seminal 2006 article highlighting the fundamental challenges of traditional ORM approaches. BDR Pattern represents one answer to the problems he raised.