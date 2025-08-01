<?php

declare(strict_types=1);

namespace Ray\MediaQuery\SemanticLog\Example;

use Koriym\SemanticLogger\SemanticLogger;
use Ray\MediaQuery\SemanticLog\Context\DatabaseContext;
use Ray\MediaQuery\SemanticLog\Context\EntityContext;
use Ray\MediaQuery\SemanticLog\Context\EventContext;
use Ray\MediaQuery\SemanticLog\Context\QueryContext;
use Ray\MediaQuery\SemanticLog\Context\ResultContext;

use function json_encode;

use const JSON_PRETTY_PRINT;

final class BasicQueryExample
{
    public function execute(): string|false
    {
        $logger = new SemanticLogger();

        // OPEN: Start query operation
        $queryId = $logger->open(new QueryContext(
            queryId: 'user_list',
            operation: 'select',
            sqlFile: 'user_list.sql',
            sqlContent: 'SELECT * FROM users WHERE status = :status',
        ));

        // EVENT: Database connection established
        $logger->event(new EventContext(
            message: 'Database connection established',
            level: 'info',
            data: ['dsn' => 'mysql:host=localhost;dbname=app'],
        ));

        // EVENT: SQL query execution
        $dbId = $logger->open(new DatabaseContext(
            operation: 'execute',
            dsn: 'mysql:host=localhost;dbname=app',
            executionTime: 0.0125,
            affectedRows: null,
        ));

        $logger->event(new EventContext(
            message: 'Query executed successfully',
            level: 'info',
            data: ['rows_found' => 5],
        ));

        $logger->close(new ResultContext('success'), $dbId);

        // EVENT: Entity hydration
        $entityId = $logger->open(new EntityContext(
            operation: 'hydrate',
            entityClass: 'App\\Entity\\User',
            fetchMethod: 'FetchClass',
            entityCount: 5,
        ));

        $logger->event(new EventContext(
            message: 'Entities hydrated successfully',
            level: 'info',
        ));

        $logger->close(new ResultContext('success'), $entityId);

        // CLOSE: Complete query operation
        $logger->close(new ResultContext(
            status: 'success',
            metadata: ['total_execution_time' => 0.025, 'entities_created' => 5],
        ), $queryId);

        // Optional: Add relations for debugging context
        $relations = [
            ['rel' => 'related', 'href' => 'https://github.com/ray-di/Ray.MediaQuery', 'title' => 'Ray.MediaQuery Repository'],
            ['rel' => 'describedby', 'href' => 'https://ray-di.github.io/Ray.MediaQuery/sql/user_list.sql', 'title' => 'SQL Schema'],
        ];

        $logJson = $logger->flush($relations);

        return json_encode($logJson, JSON_PRETTY_PRINT);
    }
}
