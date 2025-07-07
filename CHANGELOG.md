# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[0.17.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.16.0...0.17.0
[0.16.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.15.1...0.16.0
[0.15.1]: https://github.com/ray-di/Ray.MediaQuery/compare/0.15.0...0.15.1
[0.15.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.14.0...0.15.0
[0.14.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.13.0...0.14.0
[0.13.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.12.0...0.13.0
[0.12.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.11.0...0.12.0
[0.11.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.10.0...0.11.0
[0.10.0]: https://github.com/ray-di/Ray.MediaQuery/compare/0.9.0...0.10.0
