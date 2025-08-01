# Ray.MediaQuery Semantic Logging

Semantic logging integration for Ray.MediaQuery using [koriym/semantic-logger](https://github.com/koriym/semantic-logger).

## Overview

This directory contains context classes, examples, and JSON schemas for implementing structured semantic logging in Ray.MediaQuery operations. Semantic logging provides complete traceability of database queries, entity hydration, and result processing.

## Directory Structure

```
src/SemanticLog/
├── Context/                    # Context classes for different operation types
│   ├── QueryContext.php       # Database query operations
│   ├── DatabaseContext.php    # Low-level database operations
│   ├── EntityContext.php      # Entity hydration and creation
│   ├── EventContext.php       # General events
│   └── ResultContext.php      # Operation results
├── Example/                    # Usage examples
│   ├── BasicQueryExample.php
│   └── InterceptorIntegrationExample.php
└── Schema/                     # JSON Schema validation files
    ├── query.json
    ├── database.json
    ├── entity.json
    ├── event.json
    └── result.json
```

## Context Classes

### QueryContext
Tracks high-level database query operations:
- `queryId`: Unique identifier for the query
- `operation`: Type of operation (select, insert, update, delete)
- `sqlFile`: Path to the SQL file
- `sqlContent`: Actual SQL content

### DatabaseContext
Tracks low-level database execution:
- `operation`: Database operation type (execute, prepare, connect)
- `dsn`: Database connection string
- `executionTime`: Query execution time in seconds
- `affectedRows`: Number of rows affected

### EntityContext
Tracks entity hydration and creation:
- `operation`: Entity operation type (hydrate, fetch, create)
- `entityClass`: Fully qualified entity class name
- `fetchMethod`: Method used (FetchClass, FetchFactory, etc.)
- `entityCount`: Number of entities processed
- `factoryClass`: Factory class used (if applicable)

### EventContext
General-purpose event logging:
- `message`: Event description
- `level`: Log level (debug, info, warning, error, etc.)
- `data`: Additional contextual data

### ResultContext
Operation results and outcomes:
- `status`: Result status (success, failure, error, warning)
- `error`: Error message (if applicable)
- `metadata`: Additional result metadata

## Usage Examples

### Basic Query Logging

```php
use Koriym\SemanticLogger\SemanticLogger;
use Ray\MediaQuery\SemanticLog\Context\QueryContext;
use Ray\MediaQuery\SemanticLog\Context\ResultContext;

$logger = new SemanticLogger();

// Start query operation
$queryId = $logger->open(new QueryContext(
    queryId: 'user_list',
    operation: 'select',
    sqlFile: 'user_list.sql',
    sqlContent: 'SELECT * FROM users WHERE status = :status'
));

// ... execute query and process results ...

// Complete operation
$logger->close(new ResultContext('success'), $queryId);

// Get structured log
$logJson = $logger->flush();
```

### Integration with DbQueryInterceptor

The `InterceptorIntegrationExample.php` shows how to integrate semantic logging within the Ray.MediaQuery interceptor for automatic logging of all database operations.

## JSON Schema Validation

Each context class includes a schema URL that points to JSON Schema files for validation:

- `query.json` - Validates QueryContext data
- `database.json` - Validates DatabaseContext data  
- `entity.json` - Validates EntityContext data
- `event.json` - Validates EventContext data
- `result.json` - Validates ResultContext data

## Benefits

1. **Complete Traceability**: Track the entire flow from query intent to final result
2. **Structured Data**: Machine-readable logs for analysis and monitoring
3. **Type Safety**: Strongly typed context objects prevent logging errors
4. **Schema Validation**: JSON schemas ensure log data consistency
5. **AI-Friendly**: Structured format enables AI systems to understand operations
6. **Debugging**: Clear correlation between operations through openId linking

## Integration Notes

- Install `koriym/semantic-logger` as a dependency
- Context classes extend `AbstractContext` for type safety
- Use `open()` → `event()` → `close()` pattern for hierarchical logging
- Call `flush()` to get complete log output and reset state
- Add relations array for additional context links (repositories, schemas, etc.)