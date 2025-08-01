# Migration Guide: Ray.MediaQuery 1.0.0-rc1

This guide provides detailed instructions for migrating to Ray.MediaQuery 1.0.0-rc1.

## Web API Functionality Migration

**Web API functionality has been moved to a separate package `ray/web-query`.**

### Step 1: Install the Web Query Package

```bash
composer require ray/web-query
```

### Step 2: Update Module Configuration

```php
// Before (0.x)
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\WebQueryConfig;

$module = new MediaQueryModule($queries, [
    new DbQueryConfig('/path/to/sql'),
    new WebQueryConfig('/path/to/web_query.json', ['domain' => 'api.example.com'])
]);

// After (1.0.0-rc1)
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\WebQuery\MediaQueryWebModule;
use Ray\WebQuery\WebQueryConfig;

// Database module
$dbModule = new MediaQueryModule($queries, new DbQueryConfig('/path/to/sql'));

// Web API module (separate)
$webModule = new MediaQueryWebModule($queries, new WebQueryConfig('/path/to/web_query.json', ['domain' => 'api.example.com']));

// Install both modules
$injector = new Injector([$dbModule, $webModule]);
```

### Step 3: Update Import Statements

```php
// Update Web API related imports
use Ray\WebQuery\Annotation\WebQuery;
use Ray\WebQuery\MediaQueryWebModule;
use Ray\WebQuery\WebQueryConfig;
```

## Annotation to Attributes Migration

**Doctrine Annotations have been removed in favor of PHP 8 Attributes.**

### Automated Migration with Rector

1. Install Rector:
```bash
composer require --dev rector/rector
```

2. Create `rector.php`:
```php
<?php
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;

return RectorConfig::configure()
    ->withRules([
        AnnotationToAttributeRector::class,
    ]);
```

3. Run migration:
```bash
vendor/bin/rector process src/
```

### Manual Migration Examples

#### Database Queries

```php
// Before (Annotation)
/**
 * @DbQuery("user_list")
 * @Pager(perPage=10, template="/{?page}")
 */
public function getList(): array

// After (Attribute)
#[DbQuery('user_list')]
#[Pager(perPage: 10, template: '/{?page}')]
public function getList(): array
```

#### Web API Queries

```php
// Before (Annotation)
/**
 * @WebQuery("api_endpoint")
 */
public function callApi(string $id): array

// After (Attribute)
use Ray\WebQuery\Annotation\WebQuery;

#[WebQuery('api_endpoint')]
public function callApi(string $id): array
```

### Step 4: Remove Doctrine Annotations

After migration is complete:
```bash
composer remove doctrine/annotations
```

## CamelCaseTrait Migration

**CamelCaseTrait has been deprecated in favor of constructor property promotion and StringCase utility.**

### Before (CamelCaseTrait)

```php
use Ray\MediaQuery\CamelCaseTrait;

class User
{
    use CamelCaseTrait;
    
    public string $userName;
    public string $emailAddress;
}

// Usage
$user = new User();
$user->user_name = 'John Doe';        // Automatic conversion via __set
$user->email_address = 'john@example.com';
```

### After (Constructor Property Promotion + StringCase)

```php
use Ray\MediaQuery\StringCase;

class User
{
    public function __construct(
        public readonly string $userName,
        public readonly string $emailAddress,
    ) {}
}

// Usage - convert data before instantiation
$data = [
    'user_name' => 'John Doe',
    'email_address' => 'john@example.com'
];

// Convert keys from snake_case to camelCase
$camelData = [];
foreach ($data as $key => $value) {
    $camelData[StringCase::camel($key)] = $value;
}

$user = new User(...$camelData);
// OR using array_combine for more complex scenarios
$camelKeys = array_map([StringCase::class, 'camel'], array_keys($data));
$user = new User(...array_combine($camelKeys, array_values($data)));
```

**Note:** The spread operator (`...`) used in these examples requires PHP 7.4+. Since you're already using PHP 8.1+, this feature is available.

### Database Query Solutions

**For database queries with snake_case columns:**

1. **SQL Aliases (Recommended)**:
   ```sql
   SELECT 
       user_name AS userName,
       email_address AS emailAddress 
   FROM users WHERE id = :id
   ```

2. **Custom Fetch Implementation**:
   ```php
   final class CamelCaseFetch implements FetchInterface
   {
       public function __construct(private string $entity) {}
       
       public function fetchAll(PDOStatement $stmt, InjectorInterface $injector): array
       {
           $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
           $result = [];
           
           foreach ($rows as $row) {
               $camelData = [];
               foreach ($row as $key => $value) {
                   $camelData[StringCase::camel($key)] = $value;
               }
               $result[] = new ($this->entity)(...$camelData);
           }
           
           return $result;
       }
   }
   ```

3. **Factory Pattern**:
   ```php
   class UserFactory
   {
       public static function fromDbRow(array $row): User
       {
           return new User(
               userName: $row['user_name'],
               emailAddress: $row['email_address'],
           );
       }
   }
```

### StringCase Utility Methods

```php
use Ray\MediaQuery\StringCase;

// Convert snake_case to camelCase
$camelCase = StringCase::camel('user_name');      // 'userName'
$camelCase = StringCase::camel('api_key');        // 'apiKey'

// Convert camelCase to snake_case  
$snakeCase = StringCase::snake('userName');       // 'user_name'
$snakeCase = StringCase::snake('apiKey');         // 'api_key'
```

### Technical Reasons for Removal

**CamelCaseTrait cannot be used with modern PHP practices:**

1. **PHP 8.2+ Dynamic Properties**: Creation of dynamic properties is deprecated and will cause warnings
   ```php
   // CamelCaseTrait approach - problematic in PHP 8.2+
   class User {
       use CamelCaseTrait;
       public string $userName;
   }
   // PDO::FETCH_CLASS calls __set('user_name', $value)
   // Creates dynamic property $user_name (deprecated in PHP 8.2+)
   ```

2. **Readonly Properties Incompatibility**: `readonly` properties cannot be modified after initialization
   ```php
   // This approach doesn't work
   class User {
       public function __construct(
           public readonly string $userName, // Cannot be modified after construction
       ) {}
       
       public function __set(string $name, mixed $value): void {
           $this->userName = $value; // Error! Cannot modify readonly property
       }
   }
   ```

3. **PDO::FETCH_CLASS Behavior**: Always calls `__set` for non-matching property names
   - Database column: `user_name`
   - PHP property: `$userName` 
   - PDO calls `__set('user_name', $value)` because names don't match

### Benefits of the New Approach

1. **PHP 8.2+ Compatibility**: No dynamic property warnings
2. **Readonly Support**: Works with `readonly` properties for immutability
3. **Type Safety**: Constructor property promotion provides compile-time type checking
4. **Performance**: No magic methods overhead
5. **IDE Support**: Better autocompletion and refactoring support
6. **Explicit Conversion**: Clear data transformation at instantiation time

## MediaQueryModule Configuration

**MediaQueryModule constructor now only accepts DbQueryConfig instances.**

### Before

```php
$module = new MediaQueryModule($queries, [
    new DbQueryConfig('/path/to/sql'),
    new WebQueryConfig('/path/to/web_query.json', ['domain' => 'api.example.com'])
]);
```

### After

```php
// Database only
$module = new MediaQueryModule($queries, new DbQueryConfig('/path/to/sql'));

// For Web API, use separate module
$webModule = new MediaQueryWebModule($queries, new WebQueryConfig('/path/to/web_query.json', ['domain' => 'api.example.com']));
```

## Testing Your Migration

1. **Run Tests**: Ensure all tests pass after migration
```bash
composer test
```

2. **Static Analysis**: Check for type errors
```bash
composer sa
```

3. **Code Style**: Verify code formatting
```bash
composer cs
```

4. **Dependency Check**: Verify all dependencies are correctly declared
```bash
./vendor-bin/tools/vendor/maglnet/composer-require-checker/bin/composer-require-checker check ./composer.json
```

## Troubleshooting

### Common Issues

1. **Missing Web Query Classes**: Install `ray/web-query` package
2. **Annotation Errors**: Convert annotations to attributes using Rector
3. **CamelCaseTrait Errors**: Update to use StringCase with constructor property promotion
4. **Type Errors**: Enable strict typing and fix type declarations

### Getting Help

- Check the [CHANGELOG.md](./CHANGELOG.md) for breaking changes
- Review the [README.md](./README.md) for updated usage examples
- File issues at: https://github.com/ray-di/Ray.MediaQuery/issues