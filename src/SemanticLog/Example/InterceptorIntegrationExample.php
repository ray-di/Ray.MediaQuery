<?php

declare(strict_types=1);

namespace Ray\MediaQuery\SemanticLog\Example;

use Koriym\SemanticLogger\SemanticLogger;
use Ray\MediaQuery\SemanticLog\Context\DatabaseContext;
use Ray\MediaQuery\SemanticLog\Context\EntityContext;
use Ray\MediaQuery\SemanticLog\Context\EventContext;
use Ray\MediaQuery\SemanticLog\Context\QueryContext;
use Ray\MediaQuery\SemanticLog\Context\ResultContext;

use function count;
use function gettype;
use function is_array;
use function is_object;
use function json_encode;

use const JSON_PRETTY_PRINT;

/**
 * Example showing how to integrate semantic logging with DbQueryInterceptor
 */
final class InterceptorIntegrationExample
{
    private SemanticLogger $semanticLogger;

    public function __construct()
    {
        $this->semanticLogger = new SemanticLogger();
    }

    /**
     * This would be called within DbQueryInterceptor::invoke()
     *
     * @return false|string
     */
    public function logQueryExecution(
        string $queryId,
        string $sqlFile,
        string $sqlContent,
        array $parameters,
        float $executionTime,
        mixed $result,
    ): string|false {
        // OPEN: Start database query operation
        $processId = $this->semanticLogger->open(new QueryContext(
            queryId: $queryId,
            operation: 'database_query',
            sqlFile: $sqlFile,
            sqlContent: $sqlContent,
        ));

        // EVENT: Parameter binding
        $this->semanticLogger->event(new EventContext(
            message: 'Parameters bound to query',
            level: 'debug',
            data: ['parameters' => $parameters],
        ));

        // OPEN: Database execution
        $dbId = $this->semanticLogger->open(new DatabaseContext(
            operation: 'execute',
            dsn: 'sqlite::memory:',
            executionTime: $executionTime,
            affectedRows: is_array($result) ? count($result) : (is_object($result) ? 1 : 0),
        ));

        $this->semanticLogger->event(new EventContext(
            message: 'SQL query executed',
            level: 'info',
            data: ['execution_time_ms' => $executionTime * (float) 1000],
        ));

        $this->semanticLogger->close(new ResultContext('success'), $dbId);

        // OPEN: Entity processing (if result is object/array)
        if (is_array($result) || is_object($result)) {
            $entityId = $this->semanticLogger->open(new EntityContext(
                operation: 'fetch_and_hydrate',
                entityClass: is_object($result) ? $result::class : 'array',
                fetchMethod: 'auto_detected',
                entityCount: is_array($result) ? count($result) : 1,
            ));

            $this->semanticLogger->event(new EventContext(
                message: 'Result processed and entities created',
                level: 'info',
            ));

            $this->semanticLogger->close(new ResultContext('success'), $entityId);
        }

        // CLOSE: Complete query operation
        $this->semanticLogger->close(new ResultContext(
            status: 'success',
            metadata: [
                'query_id' => $queryId,
                'execution_time' => $executionTime,
                'result_type' => gettype($result),
            ],
        ), $processId);

        // Add relations for traceability
        $relations = [
            ['rel' => 'related', 'href' => "https://ray-di.github.io/Ray.MediaQuery/sql/{$sqlFile}", 'title' => 'SQL File'],
            ['rel' => 'self', 'href' => "https://ray-di.github.io/Ray.MediaQuery/query/{$queryId}", 'title' => 'Query Documentation'],
        ];

        return json_encode($this->semanticLogger->flush($relations), JSON_PRETTY_PRINT);
    }
}
