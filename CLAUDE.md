# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Ray.MediaQuery is a PHP 8.2+ database access mapping framework that provides database query abstraction through attributes/annotations. It uses dependency injection (Ray.Di) and AOP (Aspect-Oriented Programming) to automatically generate implementations from interface definitions.

## Development Commands

### Testing
- `composer test` - Run PHPUnit tests only
- `composer tests` - Run complete test suite (code style, static analysis, unit tests)
- `composer coverage` - Generate test coverage report using Xdebug
- `composer pcov` - Generate test coverage report using PCOV (faster)

### Code Quality
- `composer cs` - Check coding standards with PHP CodeSniffer
- `composer cs-fix` - Auto-fix coding standard violations
- `composer sa` - Run static analysis (Psalm + PHPStan)
- `composer clean` - Clear static analysis caches
- `composer metrics` - Generate code metrics report

### Build
- `composer build` - Full build process (clean, cs, sa, pcov, metrics)

### Demo
- `php demo/run.php` - Run the working demo application

## Architecture Overview

### Core Concept
The framework implements the Repository pattern through interface-based query definitions. Instead of writing implementation classes, you define interfaces with attributes that specify SQL queries. The framework automatically generates the implementations using AOP interceptors.

### Key Components

**Dependency Injection Modules:**
- `MediaQueryModule` - Main module that orchestrates database modules
- `MediaQueryDbModule` - Handles database query configuration

**Interceptors (AOP):**
- `DbQueryInterceptor` - Intercepts `#[DbQuery]` annotated methods

**Query Processing:**
- `SqlQueryFactory` - Creates SQL query executors
- `ParamConverter` - Converts method parameters to query parameters
- `ParamInjector` - Injects dependencies into parameters (DateTime, ValueObjects)

**Entity Management:**
- `FetchClass` - Hydrates query results to PHP objects
- `FetchFactory` - Uses factory classes to create entities
- `ReturnEntity` - Handles entity return type processing

### Configuration Structure

**Database Queries:**
- SQL files stored in configured directory (e.g., `/path/to/sql/`)
- File naming: `{query_id}.sql`
- Interface methods use `#[DbQuery('query_id')]` attribute

### Testing Architecture

**Test Organization:**
- `/tests/` - Main test suite (PHP 8.2+)
- `/tests/Fake/` - Test doubles, mock objects, and fake implementations
- `/tests/sql/` - SQL files for testing

**Development Dependencies:**
- Uses `bamarni/composer-bin-plugin` for isolated tool dependencies
- Tools in `/vendor-bin/tools/` to avoid conflicts with main dependencies

## Important Development Notes

### PHP Version Compatibility
- Supports PHP 8.2 to 8.4
- Aura.Sql version compatibility: v5.x for PHP 8.2-8.3, v6.x for PHP 8.4+
- CI tests multiple PHP versions and dependency constraints

### Code Generation
- Interface implementations are auto-generated at runtime
- Generated classes cached in `/tests/tmp/` during testing
- Uses nikic/php-parser for AST manipulation

### Static Analysis Configuration
- PHPStan: Level max (strictest)
- Psalm: Error level 1 (strictest), PHP 8.4 target
- Strict typing enforced throughout codebase

### Parameter Handling
- DateTime objects automatically converted to SQL-compatible strings
- Value objects with `ToScalarInterface::toScalar()` or `__toString()` supported
- Automatic parameter injection for null defaults (e.g., current timestamp, generated UUIDs)

### Entity Hydration
- Supports both property assignment and constructor injection
- `StringCase` utility for snake_case to camelCase conversion
- Factory pattern support through `factory` attribute parameter