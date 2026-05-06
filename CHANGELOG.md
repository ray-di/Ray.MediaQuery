# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.0 - 2026-05-06

### Added
- `PostQueryInterface` for typed result building (DML results and SELECT collection wrappers) ([#85](https://github.com/ray-di/Ray.MediaQuery/pull/85), [#89](https://github.com/ray-di/Ray.MediaQuery/pull/89))
- `AffectedRows` / `InsertedRow` result types and `execAffected()` for DML ([#85](https://github.com/ray-di/Ray.MediaQuery/pull/85))
- AI-oriented reference at [`docs/llms-full.txt`](https://ray-di.github.io/Ray.MediaQuery/llms-full.txt) ([#90](https://github.com/ray-di/Ray.MediaQuery/pull/90))

### Fixed
- Pager hydration honors the `factory` on `#[DbQuery]` ([#91](https://github.com/ray-di/Ray.MediaQuery/pull/91))

## 1.0.4 - 2026-04-14

### Fixed
- Fix `DbPager` mutating the `#[Pager]` attribute instance it receives. `DbPager::__invoke()` now clones the Pager at entry so `dynamicPager()` no longer writes back to the caller's instance ([#80](https://github.com/ray-di/Ray.MediaQuery/issues/80), [#81](https://github.com/ray-di/Ray.MediaQuery/pull/81))

## [1.0.3] - 2025-01-25

### Changed
- Upgrade `phpdocumentor/reflection-docblock` from ^5.3 to ^6.0
- Upgrade `phpdocumentor/type-resolver` from ^1.6.1 to ^2.0
- Inject `DocBlockFactoryInterface` via constructor in `ReturnEntity`

### Fixed
- Fix `extractValueType` to correctly return value type for multi-parameter generics (`array<K, V>`)

## [1.0.1] - 2025-11-23

### Fixed
- Fixed line comment handling in query type detection. SQL files with line comments (`--`) containing query keywords (e.g., `-- SELECT for testing`) no longer cause incorrect query type detection. ([#76](https://github.com/ray-di/Ray.MediaQuery/pull/76))
- Added missing trailing newlines to SQL test files to follow POSIX text file standard

## [1.0.0] - 2025-11-20

Stable release. Major update to PHP 8 attributes.

### Added
- `MediaQuerySqlModule` - Simplified module configuration for SQL-only usage

### Breaking Changes
- Annotation support removed - use PHP 8 attributes instead (`#[DbQuery]`, `#[Pager]`)
- Web API functionality moved to separate `ray/web-query` package
- See [1.0.0-rc1] and [Migration Guide](./MIGRATION.md) for details

### Changed
- Updated to PHP 8 attribute-based dependencies (ray/aop ^2.19, ray/di ^2.19)
- Improved documentation

## [1.0.0-rc2] - 2025-10-03

### Changed
- Updated pagerfanta/pagerfanta dependency to ^3.5 || ^4.7

## [1.0.0-rc1] - 2025-07-30

### BREAKING CHANGES
- **Web API functionality has been moved to a separate package `ray/web-query`**
- Removed all Web Query related classes from main package:
  - `WebQueryInterceptor`, `WebApiQuery`, `WebQueryConfig`
  - `MediaQueryWebModule`, `WebApiQueryInterface`
  - `WebQuery` annotation, `WebApiList` qualifier
  - `WebApiRequestException`
- `MediaQueryModule` constructor no longer accepts `WebQueryConfig` in configs array
- **Annotation support (`doctrine/annotations`) has been removed in favor of PHP 8 Attributes**
  - All `@DbQuery`, `@Pager` annotations must be migrated to `#[DbQuery]`, `#[Pager]` attributes
- **CamelCaseTrait has been removed**
  - Use `Ray\MediaQuery\StringCase::camel()` with constructor property promotion instead
  - This promotes better code practices with explicit type safety and immutability
- **Removed dependencies:** `guzzlehttp/guzzle`, `psr/http-message`, `doctrine/annotations`

### Added
- Suggest `ray/web-query` package for Web API functionality in composer.json

### Changed
- Update ray/input-query dependency to ^1.0
- Package description changed from "Media access mapping Framework" to "Database access mapping Framework"

### Migration Guide

**For users with Web API queries:**
- Install the new web package: `composer require ray/web-query`
- Update module configuration to use separate `MediaQueryWebModule`

**For migrating from annotations to attributes:**
- Use Rector to automatically migrate: `vendor/bin/rector process`
- Update `@DbQuery` → `#[DbQuery]`, `@Pager` → `#[Pager]`
- Remove `doctrine/annotations` dependency after migration

**For migrating from CamelCaseTrait:**
- Replace with constructor property promotion and `StringCase` utility
- Use `StringCase::camel()` and `StringCase::snake()` for conversions

**For DB-only users:** No changes required.

**Detailed migration instructions:** See [MIGRATION.md](./MIGRATION.md)

## [0.17.0] - 2025-07-07

### Added
- Support for Input object flattening in ParamConverter with #[Input] attribute
- Enhanced parameter conversion with conflict detection for Input objects
- Comprehensive test coverage for Input object processing

### Changed

- Update ray/input-query dependency to ^0.2.0
- Refactor ParamConverter with improved type annotations and static analysis compliance

### Fixed
- All static analysis errors (Psalm and PHPStan)
- Remove unreachable code and add defensive assertions
- Improve type safety throughout the codebase

## [0.16.0] - 2024-12-22

### Added
- SQL template annotation and module for query configuration
- PerformSql interface and SQL template configuration
- MediaQuerySqlTemplateModule for SQL template-based queries
- PerformTemplatedSql class for template execution

### Changed
- Extract PerformSql functionality and update SqlQuery to utilize it

## [0.15.0] - 2024-11-30

### Added
- PHP 8.4 support
- Support for aura/sql version 6.0
- Override attributes to mark method overrides
- Final class declarations for better inheritance control

### Changed
- Update PHP version constraint to support 8.1-8.4
- Update dependencies for PHP 8.4 compatibility
- Mark classes as final and add #[Override] annotations

## [0.14.0] - 2024-10-01

### Added
- Support for non-JSON responses in WebApiQuery
- getStringBody and getHttpMessage methods to WebApiQuery
- Exception for unsupported return types in WebQueryInterceptor

### Changed
- Refactor ClassesInDirectories to remove BetterReflection dependency

## [0.13.0] - 2024-03-15

### Added
- Support for nullable parameters in ParamInjector
- Support for array type in ParamConverter
- Support for BackedEnum

### Changed
- Drop PHP 8.0 support
- Update minimum ray/di dependency to ^2.14

## [0.12.0] - 2023-11-20

### Added
- DbPager functionality
- FetchMode for fetch mode and args
- Factory method injection support with "factory" parameter
- Support for koriym/csv-entities

### Changed
- Extract Fetch functionality
- Extract DbPager
- Refactor FetchMode

## [0.11.0] - 2023-10-15

### Added
- InvalidEntityException for better error handling
- FetchFactory support
- Factory pattern support through factory attribute parameter

### Changed
- Throw InvalidEntityException when entity is not valid

## [0.10.0] - 2023-08-15

### Added
- MediaQueryLogger for improved logging
- Queries::fromDir method

## Earlier versions

Please refer to the git history for changes in earlier versions.

[1.0.3]: https://github.com/ray-di/Ray.MediaQuery/compare/1.0.1...1.0.3
[1.0.1]: https://github.com/ray-di/Ray.MediaQuery/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/ray-di/Ray.MediaQuery/compare/1.0.0-rc2...1.0.0
[1.0.0-rc2]: https://github.com/ray-di/Ray.MediaQuery/compare/1.0.0-rc1...1.0.0-rc2
[1.0.0-rc1]: https://github.com/ray-di/Ray.MediaQuery/compare/0.17.0...1.0.0-rc1
[0.17.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.16.0...0.17.0
[0.16.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.15.1...0.16.0
[0.15.1]: https://github.com/ray-di/Ray.MediaQuery/compare/0.15.0...0.15.1
[0.15.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.14.0...0.15.0
[0.14.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.13.0...0.14.0
[0.13.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.12.0...0.13.0
[0.12.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.11.0...0.12.0
[0.11.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.10.0...0.11.0
[0.10.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.9.0...0.10.0
