# Recipe: Parallel SQL with BDR + BEAR.Async

A BDR Repository describes **what** to query, not **when** or **how**. That last piece is what lets per-entity SQL go parallel for free — once each Repository call lives inside its own `ResourceObject`, [BEAR.Async](https://github.com/bearsunday/BEAR.Async) parallelises them through `#[Embed]` without any change to the Repository or its SQL.

This recipe shows how to compose the two libraries. No new abstraction is introduced on either side — only a pair of conventions on the call site.

## When this is useful

- One page needs to issue several independent SQL queries (user + posts + comments, dashboard widgets, search facets, …)
- You already write SQL per query and don't want an ORM to fuse them into one mega-statement
- You want to keep development synchronous (PHP-FPM, easy debugging) and only switch to parallel execution at the application boundary (ext-parallel for PHP-FPM / Apache, ext-swoole for long-running servers)

## Prerequisites

```bash
composer require ray/media-query
composer require bear/async
```

You should already be comfortable with the [BDR Pattern](./BDR_PATTERN.md) — `#[DbQuery]` interface, factory, immutable domain object. BEAR.Async expects BEAR.Sunday-style resources (`ResourceObject` + `#[Embed]`).

## The recipe

Split each SQL into its own `ResourceObject`, then let an aggregate resource compose them with `#[Embed]`:

```php
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\ResourceObject;
use Ray\MediaQuery\Annotation\DbQuery;

// 1. Immutable domain object — the snapshot returned by the Repository
final class UserAccount
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}

// 2. BDR Repository — SQL lives in var/sql/user.sql.
//    UserFactory hydrates the row into UserAccount (see BDR_PATTERN.md).
interface UserRepositoryInterface
{
    #[DbQuery('user', factory: UserFactory::class)]
    public function getUser(int $id): UserAccount;
}

// 3. One ResourceObject per SQL — small, focused, no orchestration
class User extends ResourceObject
{
    public function __construct(private UserRepositoryInterface $repo)
    {
    }

    public function onGet(int $id): static
    {
        $this->body = ['user' => $this->repo->getUser($id)];

        return $this;
    }
}

// 4. Aggregate resource — declares the relationship, nothing else.
//    AsyncLinker parallelises the three Embeds automatically.
class UserDashboard extends ResourceObject
{
    #[Embed(rel: 'user',     src: 'app://self/user{?id}')]
    #[Embed(rel: 'posts',    src: 'app://self/user/posts{?id}')]
    #[Embed(rel: 'comments', src: 'app://self/user/comments{?id}')]
    public function onGet(int $id): static
    {
        return $this;
    }
}
```

That is the entire recipe. The dashboard never mentions concurrency, threads, coroutines, or promises. The three Repositories never coordinate.

## How it works

- **BDR keeps SQL declarative**: each `#[DbQuery]` method describes one query and returns an immutable domain object. SQL stays in `var/sql/*.sql`.
- **`ResourceObject` makes the query addressable**: `app://self/user?id=1` is a URI. `#[Embed]` declares "this aggregate needs that resource" — a relationship, not a call.
- **AsyncLinker picks the execution strategy**: at runtime it sees the three `#[Embed]` declarations as a level of independent requests and dispatches them in parallel via ext-parallel workers (PHP-FPM / Apache) or Swoole coroutines (long-running server). With neither extension loaded the same code runs sequentially per request — which is fine for PHP-FPM, since each request is its own process.

The call site never changes. Choosing sequential vs. parallel happens at the application boundary (entrypoint / module), not inside the Repository or the resource.

## Measured impact

BEAR.Async ships cold one-shot CLI benchmarks and steady-state HTTP benchmarks (`wrk`) for a dashboard that fans out to 8 independent SQL-backed embeds. Numbers and adapter selection guidance live in [BEAR.Async / docs/benchmark-results.md](https://github.com/bearsunday/BEAR.Async/blob/1.x/docs/benchmark-results.md).

The headline: as soon as a page has more than one independent SQL, this recipe makes the page faster by the number of embeds (minus pool/runtime overhead). The Repository code stays unchanged.

## Swoole notes

When running on ext-swoole, coroutines share process memory and must not share a `PDO` connection across boundaries. Install `PdoPoolModule` from BEAR.Async so each coroutine borrows a pooled `PDO`. Connection acquisition is lazy — the pool is not touched at `#[Embed]` declaration time, only when each request actually runs.

See [BEAR.Async README — Swoole execution](https://github.com/bearsunday/BEAR.Async#swoole-execution-ext-swoole) for details.

## See also

- [BDR Pattern Guide](./BDR_PATTERN.md) — the Repository + Factory + Domain Object foundation
- [BEAR.Async](https://github.com/bearsunday/BEAR.Async) — `#[Embed]` parallelisation library for BEAR.Sunday
- [BEAR.Async benchmark results](https://github.com/bearsunday/BEAR.Async/blob/1.x/docs/benchmark-results.md) — cold CLI and steady-state HTTP numbers
- [Parallel Resource Execution manual (BEAR.Sunday docs)](https://bearsunday.github.io/manuals/1.0/en/async.html)
