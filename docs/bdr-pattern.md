---
layout: default
title: BDR Pattern Guide
description: "Practical guide to the Business Domain Repository Pattern: explicit SQL, factories, and immutable domain objects."
lang: en
permalink: /bdr-pattern/
---

# Business Domain Repository Pattern (BDR Pattern) Practical Guide

[日本語 (Japanese)]({{ '/bdr-pattern/ja/' | relative_url }})

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

### Read-Only, Immutable Domain Objects

**Critically, domain objects in the BDR Pattern are read-only and immutable.** They represent a snapshot of the database at a specific point in time. These objects:

- **Have no `save()` methods** - They don't persist themselves
- **Have no setters** - State cannot be modified after creation
- **Are query results** - They represent the "read" side of your architecture

This immutability is intentional and brings important benefits:
- **Thread-safe by default** - Safe to share across concurrent operations
- **Predictable behavior** - State never changes unexpectedly
- **Clear intent** - Separation between queries (reading data) and commands (changing data)

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

    // Note: No save(), update(), or setter methods
    // This object is a read-only snapshot
}
```

In the BDR Pattern, objects are not mere data containers but domain objects containing business logic. They answer questions about the business domain but don't change the database themselves.

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
            ruleEngine: $this->ruleEngine,
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
- Implicit dependencies difficult to understand

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

<h2 id="faq">FAQ</h2>

### Q: How do I save modified objects back to the database?

**A: You don't save the query object itself.** Objects in the BDR Pattern are read-only Query models: projections shaped for a screen, report, API response, or use case. When state must change, model that change as a Command-side decision.

1. **Name the intent** - `ProcessOrder`, `DeactivateUser`, `ChangeShippingAddress`
2. **Let the Command model decide** - enforce domain consistency and make success/failure reasons explicit
3. **Persist the result** - execute the necessary UPDATE, INSERT, or DELETE in the command flow

This follows the **CQRS (Command Query Responsibility Segregation)** principle:

```php
// Query side (BDR Pattern): projection for the current use
$order = $this->orderQuery->getOrder($id);
if ($order->canShowProcessAction()) {
    // The application may offer the action, but the Command owns the final decision.
    $this->processOrder->execute($id, new DateTimeImmutable());
}

// processOrder may use explicit write SQL:
// UPDATE orders SET status = 'processed', processed_at = :timestamp WHERE id = :id
```

The separation is intentional:
- **Command models** protect domain consistency and decide whether a business action may happen
- **Query models** shape data for display or reporting and can be replaced when that use changes
- **SQL** is naturally good at projection: JOINs, aggregations, calculations, and denormalized result shapes

### Q: Is this the CQRS pattern?

**A: Yes. BDR expresses the Query side of CQRS.** But this is not mainly about placing read repositories and write repositories in different locations. It is about separating concerns and models.

The starting point is simple: reads and writes want different models.

The write side needs a domain model that protects consistency. It carries intent, behavior, invariants, and failure reasons. It answers, "May this business action happen?"

The read side often wants denormalized, flattened data for a screen, report, or API response. It answers, "What shape is useful to display now?" Trying to satisfy both with one Repository or Entity model creates friction.

CQRS is often mistaken for a physical architecture: separate databases, separate infrastructure, separate repository locations. Those may be useful implementation choices, but they are not the essence. The essence is that Command is business decision, and Query is display structure.

SQL already has this Query-side character. A `SELECT` can join, aggregate, calculate, and project a result into the exact structure needed without pretending that structure is the canonical domain model. In BDR, the SQL file defines that projection, and the factory/domain object gives it a typed PHP surface.

The Query model may be disposable. If a screen, report, or API response changes, write another `SELECT` and another small read model. That is not a DRY violation; it is the point of CQRS: different concerns get different models.

### Q: Won't calling external APIs in factories slow down list retrievals?

**A: Yes, without proper strategy.** This is essentially an N+1 problem variant. Here are strategies to mitigate:

**1. Batch Requests**
```php
final class ProductDomainFactory
{
    private array $priceCache = [];

    public function factory(string $id, string $name): ProductDomainObject
    {
        // Prices fetched in batch before factory calls
        $price = $this->priceCache[$id] ?? $this->priceService->getPrice($id);
        return new ProductDomainObject($id, $name, $price);
    }

    public function warmPriceCache(array $productIds): void
    {
        // Fetch all prices in one API call
        $this->priceCache = $this->priceService->getPrices($productIds);
    }
}
```

**2. Lazy Loading**
```php
final readonly class ProductDomainObject
{
    public function __construct(
        private string $id,
        private PriceProvider $priceProvider,
    ) {}

    public function getCurrentPrice(): float
    {
        // Only fetch when actually needed; cache inside the provider, not this readonly object.
        return $this->priceProvider->getPrice($this->id);
    }
}
```

**3. Strategic Data Loading**
```php
// List view: Don't load expensive data
#[DbQuery('product_list_simple', factory: ProductListFactory::class)]
public function getProductList(): array;

// Detail view: Load everything including external data
#[DbQuery('product_detail', factory: ProductDetailFactory::class)]
public function getProduct(string $id): ProductDomainObject;
```

The key is **being intentional** about when and how you load data. The factory pattern gives you complete control over this strategy.

## BEAR.Sunday Integration

BEAR.Sunday is a resource-oriented PHP application framework. Application operations are represented as URI-addressable `ResourceObject`s, and `#[Embed]` declares relationships between those resources.

That boundary is useful for BDR. The Repository still declares **what** SQL to run, while BEAR.Sunday and BEAR.Async decide **when** and **how** independent resource requests run. Repository interfaces and SQL files do not change.

- [BDR + BEAR.Async: Parallel SQL Recipe](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BEAR_ASYNC_RECIPE.md) — wrap each Repository call in a `ResourceObject` and let `#[Embed]` parallelise them at the application boundary.

## References

- [Object-Relational Mapping is the Vietnam of Computer Science](https://blog.codinghorror.com/object-relational-mapping-is-the-vietnam-of-computer-science/) - Jeff Atwood (2006)
