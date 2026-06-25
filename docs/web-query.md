---
layout: default
title: Ray.WebQuery — Web Response Mapping
description: "Manual for Ray.WebQuery: map HTTP API responses to typed, immutable domain objects with #[WebQuery], using the same factory and PostFetch mechanism as #[DbQuery]."
lang: en
permalink: /web-query/
---

# Ray.WebQuery — Web Response Mapping

[日本語 (Japanese)]({{ '/web-query/ja/' | relative_url }})

[Ray.WebQuery](https://github.com/ray-di/Ray.WebQuery) brings the [BDR Pattern]({{ '/bdr-pattern/' | relative_url }}) to HTTP. You declare an API call as an interface method annotated with `#[WebQuery]`, and the response is mapped to typed, immutable domain objects — through the same factory and `PostFetch` machinery that `#[DbQuery]` uses for SQL.

It is a separate package built on `ray/media-query`, which provides the core infrastructure (parameter injection, logging, …). Because it lives in the same `Ray\MediaQuery` namespace, the concepts carry over directly from the [manual]({{ '/reference/' | relative_url }}).

## Installation

```bash
composer require ray/web-query
```

## Define an interface

Annotate each method with `#[WebQuery]`, giving it a query ID:

```php
use Ray\MediaQuery\Annotation\WebQuery;

interface UserApiInterface
{
    #[WebQuery('user_item')]
    public function get(string $id): array;
}
```

## Configuration file

`web_query.json` maps each query ID to an HTTP method and a URI template:

```json
{
  "webQuery": [
    {"id": "user_item", "method": "GET", "path": "https://{domain}/users/{id}"}
  ]
}
```

## Install the module

`MediaQueryWebModule` is installed into `MediaQueryBaseModule`, and the interfaces are registered with `Queries::fromClasses()`:

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

URI template variables are filled from the method arguments and the bindings passed to `WebQueryConfig`: here `{domain}` comes from the bindings and `{id}` from the `get()` argument.

## Response types

When no factory or entity is involved, the method's return type selects how the raw HTTP response is handled:

| Return type              | Result                        |
|--------------------------|-------------------------------|
| `array`                  | JSON body decoded to an array |
| `string`                 | Raw response body             |
| PSR-7 `MessageInterface` | The HTTP message object       |

## Mapping responses to a domain object

Instead of a raw array, a method can return typed, immutable domain objects. Give `#[WebQuery]` a `factory` (and a `type`) — the same factory mechanism `#[DbQuery]` provides for SQL, applied to HTTP responses:

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

The factory is resolved through the DI injector, so it can depend on domain services and apply business logic while building the object:

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

The decoded JSON is passed to the factory method as **named arguments**: each JSON key is matched to a parameter by name, unknown keys are ignored, and a missing required argument throws `MissingResponseKeyException`. (This is the web counterpart of media-query's positional `PDO::FETCH_FUNC` binding.) The factory method is named `factory` by default.

### Single object vs. list: `type`

`type` selects whether one object or a list is produced:

| `type`       | JSON response          | Result          |
|--------------|------------------------|-----------------|
| `'row'`      | object `{...}`         | one object      |
| `'row_list'` | array `[{...}, {...}]` | `array<Object>` |

`type` defaults to `'row_list'`. A `'row'` method whose response is a list takes the first element; a `'row_list'` method whose response is a single object wraps it into a one-element list.

## Entity hydration without a factory

You can map straight to an entity **without** a factory: when the return type (or the `@return array<Entity>` docblock) is a class, each response row is hydrated through the entity constructor, using the same named-argument binding.

```php
#[WebQuery('product_item', type: 'row')]
public function get(string $id): Product; // built via Product::__construct
```

A class without a constructor throws `EntityWithoutConstructorException`, and an unresolvable class throws `InvalidWebEntityException`.

## Composing results with PostFetch

To wrap or aggregate the fetched objects into another type (totals, metadata, …), let the return type implement `PostFetchInterface`. Its static `fromContext()` receives the fetch result and returns the final object. It runs after the factory and carries no dependencies by design.

It is the web analogue of media-query's `PostQueryInterface`, named *PostFetch* because a web call is a single fetch, with no multi-statement query context to span.

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

`PostFetchContext` exposes the full context of the fetch:

| Property    | Type                    | Description                                       |
|-------------|-------------------------|---------------------------------------------------|
| `$result`   | `mixed`                 | The mapped fetch result (object or list)          |
| `$query`    | `array<string, string>` | The original method arguments                     |
| `$webQuery` | `WebQuery`              | The `#[WebQuery]` annotation (`id`, `type`, `factory`) |

## Error handling

All exceptions live under `Ray\MediaQuery\Exception`:

| Exception                           | Raised when                                                                                  |
|-------------------------------------|----------------------------------------------------------------------------------------------|
| `MissingResponseKeyException`       | A required constructor/factory parameter is absent from the response                         |
| `InvalidWebFactoryException`        | `factory:` is neither a callable static method nor a DI-constructable class (missing or non-public) |
| `InvalidWebEntityException`         | The return-type entity class cannot be found                                                 |
| `EntityWithoutConstructorException` | Entity hydration is requested for a class with no constructor                                |

## Backward compatibility

The raw-response paths are unchanged: with no `factory:` and a return type of `array`, `string`, or PSR-7 `MessageInterface`, the behaviour is identical to a plain HTTP call.

## See also

- [BDR Pattern Guide]({{ '/bdr-pattern/' | relative_url }}) — the design philosophy behind the factory and immutable domain objects.
- [Ray.MediaQuery Manual]({{ '/reference/' | relative_url }}) — the SQL counterpart (`#[DbQuery]`), parameter handling, and pagination.
- [Ray.WebQuery on GitHub](https://github.com/ray-di/Ray.WebQuery)
