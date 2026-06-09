# Ray.MediaQuery PHPStan Extension

Static analysis rules for Ray.MediaQuery query contracts.

The extension is bundled with `ray/media-query` as an optional development
feature. It is not a runtime dependency. Projects that want to enable it should
install PHPStan separately:

```bash
composer require --dev phpstan/phpstan
```

## Configuration

Include the extension from `phpstan.neon` and configure SQL directories:

```neon
includes:
  - vendor/ray/media-query/src-sa/extension.neon

parameters:
  rayMediaQuery:
    sqlDirectories:
      - docs/tutorial/src/sql
```

When developing this repository itself, include `src-sa/extension.neon` instead.

The MVP rules verify that `#[DbQuery]` SQL files exist and are non-empty,
that `#[Pager]` methods return `PagesInterface`, that `PagesInterface` return
methods declare `#[Pager]`, and that `factory:` classes define the configured
factory method.

## PHPStan result cache

The rules read `.sql` files from disk. PHPStan's result cache does not always
track non-PHP file changes, so local runs may keep stale diagnostics after only
SQL files change. Clear the cache with `composer clean` or
`vendor/bin/phpstan clear-result-cache` when validating SQL-only edits.
