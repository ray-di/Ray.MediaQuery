# ray/media-query-phpstan

PHPStan rules that statically verify the `#[DbQuery]` contract of
Ray.MediaQuery interfaces — the cross-artifact invariants that PHPStan core
cannot see on its own (SQL files, `#[Pager]` coherence, factory existence).

This currently lives in-repo under `vendor-bin/media-query-phpstan/`, installed as
its own isolated dependency set via `bamarni/composer-bin-plugin` (the same
convention the repo uses for `vendor-bin/tools/`). It is structured as a
self-contained Composer package so it can later be extracted to a standalone
`ray/media-query-phpstan` distribution unchanged.

## What it checks (MVP)

The MVP is intentionally scoped to checks with near-zero false positives:

| Identifier | Meaning |
| --- | --- |
| `rayMediaQuery.sqlFileNotFound` | `#[DbQuery('id')]` has no `{sqlDir}/id.sql` in any configured directory. |
| `rayMediaQuery.emptySqlFile` | The resolved `id.sql` exists but is empty. |
| `rayMediaQuery.pagerReturnMismatch` | `#[Pager]` is present but the return type is not a `PagesInterface`. |
| `rayMediaQuery.missingPagerAttribute` | The return type is a `PagesInterface` but `#[Pager]` is missing. |
| `rayMediaQuery.invalidFactory` | `factory:` names a class/method that does not exist. |

Not implemented on purpose:

- **Invalid `type:` value** — PHPStan core already reports this via the
  `'row'|'row_list'` constructor type of `DbQuery` (`argument.type`).
- **Placeholder ↔ parameter matching** — deferred until the SQL lexer lands; it
  needs `#[Input]` flattening and `#[SqlTemplate]` handling to avoid false
  positives.

## Usage

Add the extension to your `phpstan.neon` and point it at your SQL directories:

```neon
includes:
    - vendor-bin/media-query-phpstan/extension.neon

parameters:
    rayMediaQuery:
        sqlDirectories:
            - %currentWorkingDirectory%/path/to/sql
        factoryMethod: factory          # default: 'factory'
        strict: false                   # reserved for later phases
        strictPlaceholderCheck: false   # reserved for later phases
```

When `sqlDirectories` is empty the SQL existence/empty checks are skipped; the
Pager and factory checks still run. SQL ids are a flat, globally-unique
namespace, so each id is searched across every configured directory.

## Caveat: SQL changes and PHPStan's result cache

PHPStan's result cache is keyed on PHP files and their PHP dependencies. A change
to a `.sql` file alone does **not** invalidate the cache, so a stale
`sqlFileNotFound` / `emptySqlFile` result can persist locally. CI runs are
unaffected because they start from a clean cache. Locally, after changing SQL
files, run:

```bash
composer clean   # clears PHPStan + Psalm caches
```

## Developing / testing the rules

This bin carries its own `phpstan` + `phpunit` so `PHPStan\Testing\RuleTestCase`
has both available in one vendor (the repo's `vendor-bin/tools` has phpstan but no
phpunit, and the root has phpunit but no phpstan — neither can run rule tests
alone). Install it with the bamarni forward command, then run the tests; the
package `bootstrap.php` makes both the rule code and the parent project types
available, so the root binaries can drive the extension's configs:

```bash
composer bin media-query-phpstan install
./vendor/bin/phpunit -c vendor-bin/media-query-phpstan
```

Self-analyse the rule code at level max:

```bash
./vendor/bin/phpstan analyse -c vendor-bin/media-query-phpstan/phpstan.neon.dist
```

## Layout

```text
vendor-bin/media-query-phpstan/
  extension.neon              # parameters schema + service registration
  phpstan.neon.dist           # self-analysis of the rule code
  src/
    Rules/DbQueryContractRule.php
    Support/AttributeFinder.php
    Support/DbQueryAttribute.php
    Support/SqlFileResolver.php
  tests/
    Rules/DbQueryContractRuleTest.php
    Fixture/Data/*.php         # interfaces/classes analysed by the rule tests
    Fixture/sql/*.sql          # SQL fixtures
```
