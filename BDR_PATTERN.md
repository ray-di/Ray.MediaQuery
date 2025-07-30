# Business Domain Repository Pattern (BDR Pattern)

Ray.MediaQuery enables the **Business Domain Repository Pattern (BDR Pattern)** - an evolution of the traditional Repository Pattern that transforms simple database queries into rich domain objects through dependency injection and business logic integration.

## Pattern Overview

The BDR Pattern represents a paradigm shift where **Object-Oriented Programming and SQL become allies instead of adversaries**. Rather than hiding SQL behind abstraction layers, BDR Pattern embraces SQL's power while maintaining OOP principles, creating the best of both worlds: optimal database performance with clean, testable object-oriented design.

The pattern elevates data access from simple CRUD operations to sophisticated business domain object construction, making controllers remarkably simple while centralizing complex business logic in testable, reusable components.

### Traditional vs Business Domain Repository

**Traditional Repository Pattern:**
```php
class UserController
{
    public function show(string $id): Response
    {
        $user = $this->userRepo->findById($id); // Returns simple entity
        
        // Business logic scattered in controller - hard to test, poor readability
        $user->email = $this->validateEmail($user->email);
        $user->fullName = $user->first_name . ' ' . $user->last_name;
        $user->permissions = $this->permissionService->getPermissions($user->role_id);
        $user->profileImage = $this->imageService->getProfileImage($user->id);
        
        return $this->render('user.html.twig', ['user' => $user]);
    }
    
    // Testing requires mocking the entire controller and all its dependencies
    public function testShowRequiresComplexSetup(): void
    {
        // Must mock: userRepo, validateEmail, permissionService, imageService, render
        // Business logic mixed with presentation logic - difficult to isolate
    }
}
```

**Business Domain Repository Pattern (BDR Pattern):**
```php
class UserController
{
    public function show(string $id): Response
    {
        // Repository uses natural 'get' method - clear and conventional
        $domainUser = $this->userRepo->getUser($id);
        
        // Controller only renders - no business logic, perfect readability
        return $this->render('user.html.twig', ['user' => $domainUser]);
    }
}

class ProductController
{
    public function show(string $id): Response
    {
        // Invoke syntax works well for single-entity services
        $product = ($this->product)($id);
        
        return $this->render('product.html.twig', ['product' => $product]);
    }
}

// Repository interface - conventional method naming
interface UserRepositoryInterface 
{
    #[DbQuery('user_detail', factory: UserDomainFactory::class)]
    public function getUser(string $id): UserDomainObject;
    
    #[DbQuery('user_list')]
    /** @return array<UserDomainObject> */
    public function getUsers(): array;
}

// Service interface - invoke for single-purpose services
interface ProductServiceInterface 
{
    #[DbQuery('product_detail', factory: ProductDomainFactory::class)]
    public function __invoke(string $id): ProductDomainObject;
}
```

## Key Benefits

### 1. **Object Autonomy Achieved with SQL**
BDR Pattern accomplishes what was previously impossible: true object autonomy using SQL as the data foundation. Domain objects are self-contained with their own behavior and data, but their creation is powered by the full strength of SQL queries. This breakthrough proves that OOP principles and SQL performance can coexist perfectly.

### 2. **Minimal Integration Testing Strategy**  
BDR Pattern enables a revolutionary testing approach: comprehensive unit testing at each layer eliminates the need for complex integration tests. Test the SQL queries with data fixtures, test the domain factories with mocked dependencies, and test domain object behavior in isolation. When each component is thoroughly tested independently, the integration between them becomes trivially reliable.

### 3. **Controller Simplification**
Controllers become thin presentation layers that only fetch and render.

### 4. **Business Logic Centralization**
All domain logic is centralized in factories, making it reusable and testable.

### 5. **Maximum Performance with Raw SQL**
Direct SQL queries provide optimal performance without ORM overhead, enabling complex JOINs, window functions, and database-specific optimizations. No N+1 queries, no unnecessary data loading - just the exact data you need in a single optimized query.

### 6. **AI Transparency and Comprehension**
The clear separation of concerns and explicit dependencies make the codebase highly transparent to AI tools. Unlike complex ORM abstractions that obscure the relationship between code and data, BDR Pattern's explicit SQL queries and focused factories provide AI with complete context:

- **What data is accessed**: Visible in SQL files
- **How data is transformed**: Clear in factory methods  
- **What services are used**: Explicit in constructor dependencies
- **Business logic flow**: Traceable from query → factory → domain object

This transparency enables superior AI-assisted development, from code analysis to automated refactoring.

### 7. **SQL Power + DI Flexibility**
Leverage complex SQL queries while maintaining dependency injection benefits.

### 8. **Rich Domain Objects**
Transform simple database rows into sophisticated business entities.

## BDR Pattern Architecture

```
Controller
    ↓
Repository Interface (#[DbQuery] + Factory)
    ↓
SQL Query → Raw Data
    ↓
Factory (+ Injected Services)
    ↓
Rich Domain Object
```

## Core Components

### 1. Repository Interface
```php
interface ProductRepositoryInterface
{
    // Repository uses conventional 'get' methods - natural and clear
    #[DbQuery('product_detail', factory: ProductDomainFactory::class)]
    public function getProduct(string $id): ProductDomainObject;
    
    #[DbQuery('product_list', factory: ProductDomainFactory::class)]
    /** @return array<ProductDomainObject> */
    public function getProducts(int $categoryId): array;
    
    #[DbQuery('active_products', factory: ProductDomainFactory::class)]
    /** @return array<ProductDomainObject> */
    public function getActiveProducts(): array;
}

// Usage examples:
// $product = $this->productRepo->getProduct('product-123');    // Repository method
// $products = $this->productRepo->getProducts(1);              // Collection method
// $active = $this->productRepo->getActiveProducts();           // Filtered collection
```

### 2. Domain Factory
```php
final class ProductDomainFactory
{
    public function __construct(
        private CategoryService $categoryService,
        private PriceCalculator $priceCalculator,
        private ImageService $imageService,
        private ReviewService $reviewService,
    ) {}
    
    public function factory(
        string $id,
        string $name,
        int $category_id,
        float $base_price,
        int $review_count,
        float $avg_rating
    ): ProductDomainObject {
        return new ProductDomainObject(
            id: $id,
            name: $name,
            category: $this->categoryService->getCategory($category_id),
            basePrice: $base_price,
            finalPrice: $this->priceCalculator->calculate($base_price, $category_id),
            thumbnailUrl: $this->imageService->getThumbnail($id),
            reviewSummary: $this->reviewService->getSummary($review_count, $avg_rating),
            canPurchase: $this->priceCalculator->isAvailable($id),
        );
    }
}
```

### 3. Rich Domain Object
```php
final readonly class ProductDomainObject
{
    public function __construct(
        public string $id,
        public string $name,
        public CategoryObject $category,           // Rich object, not just ID
        public float $basePrice,
        public float $finalPrice,                  // Calculated price
        public string $thumbnailUrl,               // Generated URL
        public ReviewSummary $reviewSummary,       // Computed summary
        public bool $canPurchase,                  // Business rule result
    ) {}
    
    // Object autonomy: Domain objects expose behavior, not just data
    public function getDisplayPrice(): string
    {
        return number_format($this->finalPrice, 2);
    }
    
    public function hasDiscount(): bool
    {
        return $this->finalPrice < $this->basePrice;
    }
    
    public function getDiscountPercentage(): int
    {
        if (!$this->hasDiscount()) {
            return 0;
        }
        return (int) round((($this->basePrice - $this->finalPrice) / $this->basePrice) * 100);
    }
    
    public function isAffordable(float $budget): bool
    {
        return $this->finalPrice <= $budget;
    }
    
    public function getCategoryName(): string
    {
        return $this->category->name;
    }
}
```

## Advanced BDR Patterns

### Multi-Service Orchestration
```php
final class OrderDomainFactory
{
    public function __construct(
        private PaymentService $paymentService,
        private ShippingService $shippingService,
        private InventoryService $inventoryService,
        private TaxCalculator $taxCalculator,
    ) {}
    
    public function factory(string $id, string $items_json, string $region): OrderDomainObject
    {
        $items = json_decode($items_json, true);
        
        // Orchestrate multiple services
        $availableItems = $this->inventoryService->checkAvailability($items);
        $shipping = $this->shippingService->calculate($availableItems, $region);
        $tax = $this->taxCalculator->calculate($availableItems, $region);
        $paymentMethods = $this->paymentService->getAvailableMethods($region);
        
        return new OrderDomainObject(
            id: $id,
            items: $availableItems,
            shipping: $shipping,
            tax: $tax,
            total: $shipping->cost + $tax->amount + array_sum($availableItems),
            paymentMethods: $paymentMethods,
            canProcess: count($availableItems) > 0,
        );
    }
}
```

### External API Integration
```php
final class UserProfileDomainFactory
{
    public function __construct(
        private SocialMediaService $socialService,
        private NotificationService $notificationService,
        private AnalyticsService $analyticsService,
    ) {}
    
    public function factory(string $id, string $email, string $social_handles): UserProfileDomainObject
    {
        $socialData = $this->socialService->getProfiles(json_decode($social_handles, true));
        $notifications = $this->notificationService->getUnreadCount($id);
        $analytics = $this->analyticsService->getUserStats($id);
        
        return new UserProfileDomainObject(
            id: $id,
            email: $email,
            socialProfiles: $socialData,
            unreadNotifications: $notifications,
            activityStats: $analytics,
            isInfluencer: $socialData->totalFollowers > 10000,
        );
    }
}
```

## Testing BDR Pattern

### Factory Unit Tests
```php
class ProductDomainFactoryTest extends TestCase
{
    public function testCreatesRichDomainObject(): void
    {
        // Arrange - mock all dependencies
        $categoryService = $this->createMock(CategoryService::class);
        $priceCalculator = $this->createMock(PriceCalculator::class);
        $imageService = $this->createMock(ImageService::class);
        
        $categoryService->method('getCategory')->willReturn(new CategoryObject('Electronics'));
        $priceCalculator->method('calculate')->willReturn(90.0);
        $imageService->method('getThumbnail')->willReturn('thumb.jpg');
        
        $factory = new ProductDomainFactory($categoryService, $priceCalculator, $imageService);
        
        // Act
        $product = $factory->factory('p1', 'Phone', 1, 100.0, 5, 4.5);
        
        // Assert - test the complete domain object
        $this->assertEquals('p1', $product->id);
        $this->assertEquals(90.0, $product->finalPrice);
        $this->assertTrue($product->hasDiscount());
    }
}
```

### Integration Tests
```php
class ProductRepositoryIntegrationTest extends TestCase
{
    public function testGetProductReturnsRichDomainObject(): void
    {
        // Act - test the complete BDR flow
        $product = $this->productRepository->getProduct('product-1');
        
        // Assert - verify rich domain object
        $this->assertInstanceOf(ProductDomainObject::class, $product);
        $this->assertNotEmpty($product->thumbnailUrl);
        $this->assertInstanceOf(CategoryObject::class, $product->category);
        $this->assertIsFloat($product->finalPrice);
    }
}
```

## Best Practices

### 1. **Follow OOP Principles**
- Domain objects should expose behavior, not just data
- Each object should be autonomous and self-managing
- Encapsulate business rules within domain objects

### 2. **Three-Layer Testing Strategy**
BDR Pattern enables minimal integration testing through comprehensive unit testing at each layer:

- **SQL Layer**: Test queries with database fixtures - verify correct data retrieval
- **Factory Layer**: Test transformation logic with fake service implementations - verify business rules  
- **Domain Object Layer**: Test behavior methods in isolation - verify object autonomy

Use concrete fake implementations instead of mocks - they're more maintainable, reusable across tests, and can be analyzed by AI tools. When each layer is thoroughly tested independently, integration between them becomes predictably reliable, eliminating the need for complex end-to-end integration tests.

### 3. **Single Responsibility Factories**
Each factory should focus on creating one type of domain object.

### 4. **Immutable Domain Objects**
Use `readonly` properties to ensure domain objects can't be modified after creation.

### 5. **Meaningful Method Names**
Repository methods should express business intent: `getActiveProducts()`, `getUserProfile()`.

### 6. **Error Handling**
Factories should validate data and provide meaningful error messages.

### 7. **Performance Considerations**
- Cache expensive external API calls
- Use efficient SQL queries
- Consider lazy loading for expensive operations

### 8. **Documentation**
Document complex business rules and external dependencies.

## Migration from Traditional Repository

### Step 1: Identify Business Logic in Controllers
```php
// Before: Logic scattered in controller
$user = $this->userRepo->find($id);
$user->fullName = $user->firstName . ' ' . $user->lastName;
$user->permissions = $this->permissionService->get($user->roleId);
```

### Step 2: Create Domain Factory
```php
// After: Logic centralized in factory
final class UserDomainFactory
{
    public function factory(string $firstName, string $lastName, int $roleId): UserDomainObject
    {
        return new UserDomainObject(
            fullName: $firstName . ' ' . $lastName,
            permissions: $this->permissionService->get($roleId),
        );
    }
}
```

### Step 3: Update Repository Interface
```php
interface UserRepositoryInterface
{
    #[DbQuery('user_detail', factory: UserDomainFactory::class)]
    public function getUser(string $id): UserDomainObject;
}
```

## Conclusion

The **Business Domain Repository Pattern (BDR Pattern)** represents the reconciliation of two powerful paradigms that were once considered incompatible. **SQL and OOP shake hands** in BDR Pattern, proving that the best solution isn't choosing sides, but finding the sweet spot where both excel.

Traditional ORMs tried to make SQL invisible, pretending it didn't exist. BDR Pattern takes the opposite approach: **embrace SQL as a first-class citizen** while maintaining clean object-oriented design. 

This is **domain collaboration at its finest** - BDR Pattern dissolves the boundaries between different media. SQL (declarative, set-based) and OOP (imperative, object-based) were once considered fundamentally incompatible paradigms. By melting these boundaries, BDR Pattern creates a new hybrid medium where each technology excels in its own domain while working together seamlessly. SQL handles what it does best (data retrieval and transformation), while OOP handles what it does best (behavior modeling and business logic). Instead of forcing one into the other's constraints, BDR Pattern demonstrates that:

- **SQL stays SQL**: Complex queries, JOINs, window functions - all at maximum performance
- **Objects stay objects**: Autonomous, behavior-rich domain models with proper encapsulation  
- **OOP autonomy with SQL foundation**: The impossible becomes possible - true object-oriented design powered by raw SQL performance
- **Testing becomes surgical**: Each component tested in isolation with crystal-clear boundaries
- **Code becomes readable**: Business intent expressed clearly at every level

By combining the power of SQL with dependency injection and business logic centralization, BDR Pattern enables:

- **Simplified Controllers**: Focus purely on presentation
- **Rich Domain Objects**: Complete business entities with computed properties
- **Centralized Business Logic**: Reusable, testable domain construction
- **Maximum Performance**: Direct SQL without ORM overhead
- **Service Integration**: Seamlessly incorporate external services and APIs

Ray.MediaQuery makes BDR Pattern implementation straightforward through its factory system and dependency injection integration, transforming how we think about data access in modern PHP applications.

**The long-standing war between SQL and OOP is over. They're not enemies—they're collaborators, each excelling in their own domain while building something greater together.**

## References

- [ORM is the Vietnam of Computer Science](https://blog.codinghorror.com/object-relational-mapping-is-the-vietnam-of-computer-science/) - Jeff Atwood's influential blog post that highlighted the fundamental challenges of traditional ORM approaches

---

# Advanced Factory Implementation Patterns

This section covers advanced factory patterns that implement the BDR Pattern using sophisticated dependency injection and business logic.

## Enterprise Data Enrichment

**Master Data Lookup with Repository Pattern:**
```php
final class ProductEntityFactory
{
    public function __construct(
        private CategoryRepository $categoryRepo,     // Injected by DI
        private PriceCalculator $priceCalculator,     // Injected by DI
        private ImageService $imageService,           // Injected by DI
    ) {}
    
    public function factory(string $id, string $name, int $category_id, float $base_price): Product
    {
        // Leverage injected services
        $category = $this->categoryRepo->findById($category_id);
        $finalPrice = $this->priceCalculator->calculate($base_price, $category->discountRate);
        $imageUrl = $this->imageService->getThumbnail($id);
        
        return new Product(
            id: $id,
            name: $name,
            categoryId: $category_id,
            categoryName: $category->name,        // From injected repository
            basePrice: $base_price,
            finalPrice: $finalPrice,              // Calculated with injected service
            thumbnailUrl: $imageUrl,              // Generated with injected service
        );
    }
}
```

## Data Validation and Security

**Complete Data Validation Pipeline:**
```php
final class UserEntityFactory
{
    public function __construct(
        private EmailValidator $emailValidator,       // Injected by DI
        private PhoneFormatter $phoneFormatter,       // Injected by DI
        private SecurityService $securityService,     // Injected by DI
    ) {}
    
    public function factory(string $id, string $email, string $phone, string $status): User
    {
        // Validate and sanitize data using injected services
        $validatedEmail = $this->emailValidator->validate($email);
        $formattedPhone = $this->phoneFormatter->format($phone);
        $isActive = $this->securityService->checkUserStatus($status, $id);
        
        return new User(
            id: $id,
            email: $validatedEmail,           // Validated email
            phone: $formattedPhone,           // Formatted phone number
            isActive: $isActive,              // Security-checked status
            hasValidEmail: $validatedEmail !== null,  // Computed validation result
        );
    }
}
```

## Complex Business Logic Integration

**Multi-Service Orchestration:**
```php
final class OrderEntityFactory
{
    public function __construct(
        private JsonValidator $jsonValidator,         // Injected by DI
        private TaxCalculator $taxCalculator,         // Injected by DI
        private InventoryService $inventoryService,   // Injected by DI
        private ShippingService $shippingService,    // Injected by DI
    ) {}
    
    public function factory(string $id, string $items_json, float $amount, string $region): Order
    {
        // Validate and process JSON data
        $validatedItems = $this->jsonValidator->parseAndValidate($items_json);
        
        // Calculate taxes based on region
        $tax = $this->taxCalculator->calculate($amount, $region);
        
        // Check inventory and calculate shipping
        $availableItems = $this->inventoryService->checkAvailability($validatedItems);
        $shippingCost = $this->shippingService->calculateCost($availableItems, $region);
        
        return new Order(
            id: $id,
            items: $availableItems,               // Validated and inventory-checked
            amount: $amount,
            tax: $tax,                            // Region-specific tax calculation
            shippingCost: $shippingCost,          // Dynamic shipping calculation
            total: $amount + $tax + $shippingCost, // Complete total
            canFulfill: count($availableItems) > 0,  // Business logic
        );
    }
}
```

## External API Integration

**Enrich Data from External Services:**
```php
final class AddressEntityFactory
{
    public function __construct(
        private PostalCodeService $postalCodeService,  // External API
        private GeolocationService $geoService,        // External API
        private WeatherService $weatherService,        // External API
    ) {}
    
    public function factory(string $id, string $postal_code, string $country): Address
    {
        // Enrich with external data
        $addressDetails = $this->postalCodeService->lookup($postal_code, $country);
        $coordinates = $this->geoService->getCoordinates($addressDetails->fullAddress);
        $currentWeather = $this->weatherService->getCurrent($coordinates);
        
        return new Address(
            id: $id,
            postalCode: $postal_code,
            country: $country,
            city: $addressDetails->city,              // From postal service
            region: $addressDetails->region,          // From postal service
            latitude: $coordinates->lat,              // From geo service
            longitude: $coordinates->lng,             // From geo service
            currentTemperature: $currentWeather->temp, // From weather service
        );
    }
}
```

## Conditional Entity Creation

**Polymorphic Object Creation:**
```php
final class NotificationEntityFactory
{
    public function __construct(
        private EmailService $emailService,
        private SmsService $smsService,
        private PushService $pushService,
    ) {}
    
    public function factory(string $type, string $recipient, string $data): NotificationInterface
    {
        $parsedData = json_decode($data, true);
        
        return match ($type) {
            'email' => new EmailNotification(
                recipient: $recipient,
                subject: $parsedData['subject'],
                body: $parsedData['body'],
                template: $this->emailService->getTemplate($parsedData['template_id'])
            ),
            'sms' => new SmsNotification(
                phoneNumber: $this->smsService->formatNumber($recipient),
                message: $parsedData['message'],
                priority: $parsedData['priority'] ?? 'normal'
            ),
            'push' => new PushNotification(
                deviceToken: $recipient,
                title: $parsedData['title'],
                body: $parsedData['body'],
                badge: $this->pushService->calculateBadgeCount($recipient)
            ),
            default => throw new InvalidNotificationTypeException($type),
        };
    }
}
```

## Performance Considerations

**Caching and Optimization:**
```php
final class CachedUserEntityFactory
{
    public function __construct(
        private CacheInterface $cache,
        private ProfileService $profileService,
        private PermissionService $permissionService,
    ) {}
    
    public function factory(string $id, string $email, int $role_id): User
    {
        // Cache expensive operations
        $cacheKey = "user_profile_{$id}";
        $profile = $this->cache->get($cacheKey) 
            ?? $this->cache->set($cacheKey, $this->profileService->getProfile($id), 3600);
        
        $permissions = $this->permissionService->getPermissions($role_id);
        
        return new User(
            id: $id,
            email: $email,
            profile: $profile,                    // Cached profile data
            permissions: $permissions,            // Role-based permissions
            canEdit: in_array('edit', $permissions),  // Computed permission
        );
    }
}
```

## Factory Testing Patterns

**Unit Testing with Fake Implementations:**
```php
class ProductEntityFactoryTest extends TestCase
{
    public function testFactoryCreatesProductWithEnrichedData(): void
    {
        // Arrange - Use concrete fake implementations that AI tools can analyze
        $categoryRepo = new FakeCategoryRepository([
            1 => new Category('electronics', 'Electronics', 0.1)
        ]);
        $priceCalculator = new FakePriceCalculator();
        $imageService = new FakeImageService();
        
        $factory = new ProductEntityFactory($categoryRepo, $priceCalculator, $imageService);
        
        // Act
        $product = $factory->factory('product-1', 'Smartphone', 1, 100.0);
        
        // Assert - Clear, traceable test logic
        $this->assertEquals('product-1', $product->id);
        $this->assertEquals('Smartphone', $product->name);
        $this->assertEquals('Electronics', $product->categoryName);
        $this->assertEquals(90.0, $product->finalPrice);
        $this->assertEquals('https://fake-service.com/product-1/thumb.jpg', $product->thumbnailUrl);
    }
}

// Fake implementations - reusable across tests
final class FakeCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(private array $categories = []) {}
    
    public function findById(int $id): Category
    {
        return $this->categories[$id] ?? throw new CategoryNotFoundException();
    }
}

final class FakePriceCalculator implements PriceCalculatorInterface
{
    public function calculate(float $basePrice, float $discountRate): float
    {
        return $basePrice * (1 - $discountRate);
    }
}
```