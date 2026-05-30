# Ray.MediaQuery

[![codecov](https://codecov.io/gh/ray-di/Ray.MediaQuery/branch/1.x/graph/badge.svg?token=QBOPCUPJQV)](https://codecov.io/gh/ray-di/Ray.MediaQuery)
[![Type Coverage](https://shepherd.dev/github/ray-di/Ray.MediaQuery/coverage.svg)](https://shepherd.dev/github/ray-di/Ray.MediaQuery)
[![Continuous Integration](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ray-di/Ray.MediaQuery/actions/workflows/continuous-integration.yml)

## Interface-Driven SQL for PHP

**Ray.MediaQuery lets SQL be SQL and objects be objects.**

Define a PHP interface, attach `#[DbQuery]`, write a SQL file, and Ray.MediaQuery provides the implementation through Ray.Di + AOP. Return types and docblocks drive fetching, hydration, pagination, and post-query result objects.

```php
use Ray\MediaQuery\Annotation\DbQuery;

interface UserQueryInterface
{
    #[DbQuery('user_item')]
    public function item(string $id): ?User;
}

final class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {}
}
```

```sql
-- sql/user_item.sql
SELECT id, name FROM users WHERE id = :id;
```

```php
$userQuery = $injector->getInstance(UserQueryInterface::class);
$user = $userQuery->item('user-123');
```

## Why Ray.MediaQuery?

- **Zero implementation code** — interfaces become working query objects.
- **SQL-first** — use joins, CTEs, window functions, vendor-specific SQL, and query plans directly.
- **Typed PHP results** — hydrate rows to entities, typed collections, or custom result objects.
- **Rich domain objects** — use `factory:` classes, including DI-aware factories, to create computed or service-backed objects, such as exposing `age` from a stored `birth_date`.
- **Explicit boundaries** — SQL files, PHP interfaces, and domain objects remain visible and testable.
- **AI-friendly** — no hidden query generation; the contract is readable by humans and tools.

## Installation

```bash
composer require ray/media-query
```

## Quick Start

```php
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\MediaQuerySqlModule;

final class AppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new MediaQuerySqlModule(
            interfaceDir: __DIR__ . '/Query',
            sqlDir: __DIR__ . '/sql',
        ));
        $this->install(new AuraSqlModule('sqlite::memory:'));
    }
}

interface TodoQueryInterface
{
    #[DbQuery('todo_add')]
    public function add(string $id, string $title): void;

    /** @return array<Todo> */
    #[DbQuery('todo_list')]
    public function list(): array;
}

$injector = new Injector(new AppModule());
$todoQuery = $injector->getInstance(TodoQueryInterface::class);
$todoQuery->add('todo-1', 'Write SQL');
$todos = $todoQuery->list();
```

## Result Types at a Glance

| Declaration | Meaning |
|-------------|---------|
| `array` | List of associative rows |
| `?array` + `type: 'row'` | Single associative row or `null` |
| `/** @return array<User> */ array` | Hydrated entity list |
| `?User` + `type: 'row'` | Single hydrated entity or `null` |
| `void` | Execute DML and ignore the result |
| `AffectedRows` | DML row count |
| `InsertedRow` | INSERT id and resolved bound values |
| `Pages<User>` | Lazy paginated hydrated rows |
| `PostQueryInterface` | Custom post-query result object |

## Documentation

- [Documentation Home](https://ray-di.github.io/Ray.MediaQuery/) — tutorial, reference, and AI-oriented docs.
- [Hands-on Tutorial (日本語)](https://ray-di.github.io/Ray.MediaQuery/tutorial/) — 13 chapters using SQLite. ([source](./docs/tutorial/README.ja.md))
- [Feature Reference](https://ray-di.github.io/Ray.MediaQuery/reference/) — result mapping, factories, parameter handling, pagination, and direct SQL execution. ([source](./docs/reference.md))
- [BDR Pattern Guide](./BDR_PATTERN.md) — architectural approach behind SQL + rich domain objects.
- [AI-Oriented Reference](https://ray-di.github.io/Ray.MediaQuery/llms-full.txt) — compact reference for coding agents. ([source](./docs/llms-full.txt))
- [Demo Application](./demo/) — a minimal runnable smoke test of the module wiring; the hands-on tutorial above is the full feature walkthrough.

## Philosophy

Ray.MediaQuery does not hide SQL to make objects comfortable, and it does not flatten objects to make SQL convenient. It lets both sides do what they are good at: SQL expresses data access precisely, while PHP expresses types, domain behavior, and dependency-injected object construction.
