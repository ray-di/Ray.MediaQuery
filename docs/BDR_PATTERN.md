# Business Domain Repository Pattern (BDR Pattern) Practical Guide

[日本語 (Japanese)](./BDR_PATTERN-ja.md)

## Introduction

Programmers have long grappled with the **boundary** between relational and object-oriented thinking. This problem is known as the "Object-Relational Impedance Mismatch," referring to the fundamental incompatibility between the tabular data of relational databases and the hierarchical object structures of object-oriented programming.

Traditional ORMs attempted to abstract SQL away, **making it invisible**. This abstraction created boundaries that developers constantly felt. We want to leverage the power of databases while maintaining object-oriented principles. How to reconcile these two desires has always been a challenge.

**The BDR Pattern dissolves this boundary.**

**In the BDR Pattern, SQL and OOP shake hands**. Each performs what it does best while working in harmony.

The friction caused by boundaries is eliminated. There's no longer a need for forced abstractions or for one paradigm to pretend the other doesn't exist.

## Executive Summary

The BDR Pattern presents a new paradigm where **Object-Oriented Programming and SQL work in harmony**. It achieves "OOP autonomy with SQL foundation" and enables:

**Core Value Propositions:**
- **SQL remains SQL**: Complex queries, JOINs, window functions - all at maximum performance
- **Objects remain objects**: Autonomous domain models with rich behavior
- **Leveraging both strengths**: Achieving both object-oriented design and SQL performance
- **Clear testing**: Each component can be tested independently

## Why This Matters

### Common Scenarios in Development

Complex business logic scattered across controllers. One change requires modifications to multiple methods, and testing requires numerous mock objects.

When using ORMs, we encounter characteristics such as:
- Need to handle N+1 query problems
- Constraints in expressing complex JOINs
- Difficulty predicting generated SQL
- Creative workarounds needed for performance tuning

**The BDR Pattern proposes a different approach.**

It leverages the strengths of both SQL and OOP, allowing each to shine in their respective domains.

### The BDR Pattern Approach

```php
public function showOrderDetails(string $id): Response
{
    $order = $this->orderRepo->getOrder($id);
    return $this->render('order.html.twig', ['order' => $order]);
}

// Business logic in factories
// SQL in optimized query files
// Tests in independent layers
```

Simple structure improves maintainability and readability.

## The Problem and Solution

### Traditional Approach Problems

```php
class OrderController
{
    public function show(string $id): Response
    {
        $order = $this->orderRepo->findById($id); // Simple data
        
        // Business logic scattered in controller - testing nightmare!
        $items = $this->inventoryService->checkStock($order->items);
        $tax = $this->taxCalculator->calculate($items, $order->region);
        $shipping = $this->shippingService->calculate($items, $order->region);
        $canFulfill = $this->validateOrder($items, $order->status);
        
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

## Object Autonomy

The BDR Pattern achieves something important: **true object autonomy with SQL as the foundation**.

Balancing object autonomy and SQL efficiency was traditionally considered difficult. The BDR Pattern achieves this balance. Domain objects are self-contained with their own behavior and data, while their creation is efficiently powered by SQL queries.

```php
// Leveraging the power of DI in domain objects
final readonly class UserDomainObject
{
    public function __construct(
        public string $id,
        public string $name,
        public string $role,
        // Service injected from factory
        private PermissionService $permissionService,
    ) {}
    
    // Dynamic business rules through injected service
    public function canEdit(Document $document): bool
    {
        // Impossible with ORM entities - depends on external service
        // Test env: FakePermissionService (everyone can edit)
        // Production: RealPermissionService (complex permission checks)
        return $this->permissionService->canEdit($this, $document);
    }
}
```

In the BDR Pattern, objects are not mere data containers but domain objects containing business logic.

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
        // Business logic to identify items with insufficient stock
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
        public float $tax,                      // Calculated by region
        public float $shipping,                 // Calculated shipping
        public float $total,                    // Complete total
        public bool $canFulfill,                // Business rule applied
        public array $insufficientStockItems,   // List of insufficient stock items
        // Injected business rule engine - impossible with ORM
        private BusinessRuleEngine $ruleEngine,
    ) {}
    
    // Domain object behavior
    public function getDisplayTotal(): string
    {
        return '

## Three-Layer Testing Strategy: Simple and Reliable Testing

One of the important advantages of the BDR Pattern is that **testing becomes simple and reliable**.

### Common Testing Challenges

Common challenges in testing include:
- Integration tests taking a long time to run
- Test instability due to database state dependencies
- Complex mock setups
- Intermittently failing tests

**The BDR Pattern provides a better way.**

### Why Testing Becomes Simple

Because each layer is **independent**, if each is tested individually, the combination naturally works:

1. **SQL Query**: Does it return correct data for the input?
2. **Factory**: Does it correctly transform data into domain objects?
3. **Domain Object**: Does it correctly implement business rules?

If these are individually correct, the combination is necessarily correct. **It's a logical structure.**

### 1. SQL Layer Testing

```php
class UserQueryTest extends DatabaseTestCase
{
    public function testUserByIdQuery(): void
    {
        // Prepare test data
        $this->insertUser('user-1', 'Alice', 'alice@example.com', 'editor');
        
        // Execute query
        $result = $this->executeQuery('user_by_id.sql', ['id' => 'user-1']);
        
        // Verify results
        $this->assertEquals('Alice', $result[0]['name']);
        $this->assertEquals('editor', $result[0]['role']);
    }
}
```

### 2. Factory Layer Testing

```php
class UserDomainFactoryTest extends TestCase
{
    public function testCreatesUserWithInjectedService(): void
    {
        // Inject fake service
        $permissionService = new FakePermissionService();
        $factory = new UserDomainFactory($permissionService);
        
        // Test factory
        $user = $factory->factory('user-1', 'Alice', 'alice@example.com', 'editor');
        
        // Verify object is created correctly
        $this->assertEquals('Alice', $user->name);
        $this->assertEquals('editor', $user->role);
        
        // Confirm injected service works
        $document = new Document('doc-1', 'user-1');
        $this->assertTrue($user->canEdit($document));
    }
}
```

### 3. Domain Object Testing

```php
class UserDomainObjectTest extends TestCase
{
    public function testCanEditWithDifferentPermissionServices(): void
    {
        $document = new Document('doc-1', 'user-2');
        
        // Restrictive service
        $strictService = new StrictPermissionService();
        $user1 = new UserDomainObject('user-1', 'Alice', 'alice@example.com', 'editor', $strictService);
        $this->assertFalse($user1->canEdit($document)); // Cannot edit others' documents
        
        // Permissive service
        $relaxedService = new RelaxedPermissionService();
        $user2 = new UserDomainObject('user-1', 'Alice', 'alice@example.com', 'editor', $relaxedService);
        $this->assertTrue($user2->canEdit($document)); // Editors can edit all documents
    }
}
```

Because each layer is tested independently, integration issues are extremely rare. This eliminates the need for complex and fragile integration tests.

## Practical Patterns

### Polymorphic Domain Objects

```php
final class UserDomainFactory
{
    public function factory(string $id, string $email, string $type): UserInterface
    {
        return match ($type) {
            'free' => new FreeUser($id, $email, maxStorage: 100),
            'premium' => new PremiumUser($id, $email, maxStorage: 1000),
        };
    }
}
```

### External API Integration

```php
final class ProductDomainFactory
{
    public function __construct(
        private PriceService $priceService,  // External API
    ) {}
    
    public function factory(string $id, string $name): ProductDomainObject
    {
        return new ProductDomainObject(
            id: $id,
            name: $name,
            currentPrice: $this->priceService->getCurrentPrice($id),
        );
    }
}
```

### Caching Strategy

```php
final class UserDomainFactory
{
    public function __construct(
        private CacheInterface $cache,
        private PermissionService $permissionService,
    ) {}
    
    public function factory(string $id, string $name, string $role): UserDomainObject
    {
        // Cache expensive permission lookups
        $permissions = $this->cache->remember(
            "permissions_{$role}", 
            3600, 
            fn() => $this->permissionService->getPermissions($role)
        );
        
        return new UserDomainObject($id, $name, $role, $permissions);
    }
}
```

## Migration from Existing Projects

### Step 1: Identify Business Logic

```php
// Before: Logic scattered in controller
class ProductController
{
    public function show($id)
    {
        $product = $this->repo->find($id);
        
        // Identify this business logic
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

1. **Start with new features** - Implement new features with BDR Pattern
2. **Prioritize high-traffic endpoints** - Greater performance improvement impact
3. **Leverage existing test coverage** - Migrate while utilizing existing tests
4. **Share knowledge within the team** - Share the benefits of the factory pattern

## Adapting to the AI Era: Achieving Transparency

Another advantage of the BDR Pattern is creating a **codebase transparent to AI tools**.

Complex abstraction layers of traditional ORMs were black boxes to AI:
- Unclear what SQL would be executed
- Difficult to trace where business logic exists
- Implicit dependencies hard to understand

In the BDR Pattern, everything is explicit:
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
// Factory - AI fully understands dependencies and logic
public function __construct(
    private TaxCalculator $taxCalculator,      // Explicit dependency
    private ShippingService $shippingService,  // Explicit dependency
) {}
```

This transparency enables AI assistants to deeply understand your codebase and provide more accurate suggestions and automation.

## Summary

The BDR Pattern presents **one form of domain collaboration**. It not only bridges different paradigms but also **dissolves boundaries between different media**.

SQL (declarative, set-based) and OOP (imperative, object-based). How to combine these technologies with different characteristics has been a long-standing challenge.

**The BDR Pattern provides one approach to this challenge.**

The boundaries created by traditional ORMs abstracting SQL. The BDR Pattern dissolves these and creates new harmony.

The results achieved are:
- **Controllers become simple** - Focus on presentation
- **Business logic in the right place** - Placed in factories
- **Testing is clear and independent** - Each layer ensures quality independently
- **No performance compromise** - Maximize SQL performance

**SQL and OOP work in harmony.**

In the BDR Pattern, each excels in its own domain while building something greater together.

## References

- [Object-Relational Mapping is the Vietnam of Computer Science](https://blog.codinghorror.com/object-relational-mapping-is-the-vietnam-of-computer-science/) - Jeff Atwood (2006) . number_format($this->total, 2);
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

  // Dynamic business rules through injected service
  public function getBusinessPriority(): string
  {
  // Impossible with ORM entities - depends on external service
  // Test environment: Relaxed thresholds (e.g., high priority at $100+)
  // Production: Strict thresholds (e.g., high priority at $10,000+)
  // Peak season: Different thresholds
  // VIP customers: Special rules apply
  return $this->ruleEngine->calculatePriority($this);
  }
  }
```

## Three-Layer Testing Strategy: Simple and Reliable Testing

One of the important advantages of the BDR Pattern is that **testing becomes simple and reliable**.

### Common Testing Challenges

Common challenges in testing include:
- Integration tests taking a long time to run
- Test instability due to database state dependencies
- Complex mock setups
- Intermittently failing tests

**The BDR Pattern provides a better way.**

### Why Testing Becomes Simple

Because each layer is **independent**, if each is tested individually, the combination naturally works:

1. **SQL Query**: Does it return correct data for the input?
2. **Factory**: Does it correctly transform data into domain objects?
3. **Domain Object**: Does it correctly implement business rules?

If these are individually correct, the combination is necessarily correct. **It's a logical structure.**

### 1. SQL Layer Testing

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

### 2. Factory Layer Testing

```php
class OrderDomainFactoryTest extends TestCase
{
    public function testCreatesRichDomainObject(): void
    {
        // Use fake implementations (analyzable by AI tools)
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

### 3. Domain Object Testing

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

Because each layer is tested independently, integration issues are extremely rare. This eliminates the need for complex and fragile integration tests.

## Practical Patterns

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
        // Enrich with data from external services
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
// Before: Logic scattered in controller
class ProductController
{
    public function show($id)
    {
        $product = $this->repo->find($id);
        
        // Identify this business logic
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

1. **Start with new features** - Implement new features with BDR Pattern
2. **Prioritize high-traffic endpoints** - Greater performance improvement impact
3. **Leverage existing test coverage** - Migrate while utilizing existing tests
4. **Share knowledge within the team** - Share the benefits of the factory pattern

## Adapting to the AI Era: Achieving Transparency

Another advantage of the BDR Pattern is creating a **codebase transparent to AI tools**.

Complex abstraction layers of traditional ORMs were black boxes to AI:
- Unclear what SQL would be executed
- Difficult to trace where business logic exists
- Implicit dependencies hard to understand

In the BDR Pattern, everything is explicit:
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
// Factory - AI fully understands dependencies and logic
public function __construct(
    private TaxCalculator $taxCalculator,      // Explicit dependency
    private ShippingService $shippingService,  // Explicit dependency
) {}
```

This transparency enables AI assistants to deeply understand your codebase and provide more accurate suggestions and automation.

## Summary

The BDR Pattern presents **one form of domain collaboration**. It not only bridges different paradigms but also **dissolves boundaries between different media**.

SQL (declarative, set-based) and OOP (imperative, object-based). How to combine these technologies with different characteristics has been a long-standing challenge.

**The BDR Pattern provides one approach to this challenge.**

The boundaries created by traditional ORMs abstracting SQL. The BDR Pattern dissolves these and creates new harmony.

The results achieved are:
- **Controllers become simple** - Focus on presentation
- **Business logic in the right place** - Placed in factories
- **Testing is clear and independent** - Each layer ensures quality independently
- **No performance compromise** - Maximize SQL performance

**SQL and OOP work in harmony.**

In the BDR Pattern, each excels in its own domain while building something greater together.

## References

- [Object-Relational Mapping is the Vietnam of Computer Science](https://blog.codinghorror.com/object-relational-mapping-is-the-vietnam-of-computer-science/) - Jeff Atwood (2006)
