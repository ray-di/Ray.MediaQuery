<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Queries\TodoItemInterface;
use Ray\MediaQuery\SemanticLog\Context\DatabaseContext;
use Ray\MediaQuery\SemanticLog\Context\EntityContext;
use Ray\MediaQuery\SemanticLog\Context\EventContext;
use Ray\MediaQuery\SemanticLog\Context\QueryContext;
use Ray\MediaQuery\SemanticLog\Context\ResultContext;
use RuntimeException;
use Throwable;

use function array_filter;
use function count;
use function file_put_contents;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function microtime;
use function mkdir;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class SemanticLogIntegrationTest extends TestCase
{
    private SemanticLogger $semanticLogger;
    private Injector $injector;

    protected function setUp(): void
    {
        $this->semanticLogger = new SemanticLogger();

        // Setup Ray.MediaQuery with test module
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $queries = Queries::fromClasses([TodoItemInterface::class]);
                $this->install(new MediaQueryModule($queries, [new DbQueryConfig(__DIR__ . '/sql')]));
                $this->install(new AuraSqlModule('sqlite::memory:'));
            }
        };

        $this->injector = new Injector($module);

        // Setup test database
        /** @var ExtendedPdoInterface $pdo */
        $pdo = $this->injector->getInstance(ExtendedPdoInterface::class);
        $pdo->query('CREATE TABLE IF NOT EXISTS todo (id TEXT, title TEXT)');
        $pdo->query("INSERT INTO todo (id, title) VALUES ('1', 'Test Todo Item')");
    }

    public function testSemanticLogWithActualQuery(): void
    {
        // OPEN: Start query operation with semantic logging
        $queryId = $this->semanticLogger->open(new QueryContext(
            queryId: 'todo_item',
            operation: 'select',
            sqlFile: 'todo_item.sql',
            sqlContent: 'SELECT * FROM todo WHERE id = :id',
        ));

        // EVENT: Initialize database operation
        $this->semanticLogger->event(new EventContext(
            message: 'Starting todo item query',
            level: 'info',
            data: ['id' => '1'],
        ));

        // OPEN: Database execution context
        $dbId = $this->semanticLogger->open(new DatabaseContext(
            operation: 'execute',
            dsn: 'sqlite::memory:',
            executionTime: null,
            affectedRows: null,
        ));

        $startTime = microtime(true);

        // Execute actual Ray.MediaQuery operation
        /** @var TodoItemInterface $todoItem */
        $todoItem = $this->injector->getInstance(TodoItemInterface::class);
        $result = $todoItem('1');

        $executionTime = microtime(true) - $startTime;

        // EVENT: Query executed
        $this->semanticLogger->event(new EventContext(
            message: 'Query executed successfully',
            level: 'info',
            data: ['execution_time_ms' => $executionTime * 1000],
        ));

        // CLOSE: Database operation
        $this->semanticLogger->close(new ResultContext(
            status: 'success',
            result: $result,
            resultCount: is_array($result) ? count($result) : 1,
            metadata: ['execution_time' => $executionTime],
        ), $dbId);

        // OPEN: Entity processing
        $entityId = $this->semanticLogger->open(new EntityContext(
            operation: 'hydrate',
            entityClass: is_array($result) ? 'array' : $result::class,
            fetchMethod: 'FetchAssoc',
            entityCount: 1,
        ));

        $this->semanticLogger->event(new EventContext(
            message: 'Entity hydration completed',
            level: 'info',
        ));

        $this->semanticLogger->close(new ResultContext('success'), $entityId);

        // CLOSE: Complete query operation
        $this->semanticLogger->close(new ResultContext(
            status: 'success',
            metadata: ['total_operations' => 3],
        ), $queryId);

        // Generate semantic log
        $relations = [
            ['rel' => 'related', 'href' => 'https://github.com/ray-di/Ray.MediaQuery', 'title' => 'Ray.MediaQuery'],
            ['rel' => 'describedby', 'href' => 'https://ray-di.github.io/Ray.MediaQuery/sql/todo_item.sql', 'title' => 'SQL Schema'],
        ];

        $logJson = $this->semanticLogger->flush($relations);
        $logArray = json_decode(json_encode($logJson), true);

        // Output to test tmp directory like BEAR.Resource
        $testDir = __DIR__ . '/tmp/' . self::class;
        if (! is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $outputFile = $testDir . '/' . __FUNCTION__ . '.json';
        file_put_contents($outputFile, json_encode($logJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Assertions
        $this->assertNotNull($logJson);
        $this->assertArrayHasKey('open', $logArray);
        $this->assertArrayHasKey('events', $logArray);
        $this->assertArrayHasKey('close', $logArray);
        $this->assertArrayHasKey('links', $logArray);

        // Verify open operation
        $openOperation = $logArray['open'];
        $this->assertSame('query', $openOperation['type']);
        $this->assertSame('todo_item', $openOperation['context']['queryId']);
        $this->assertSame('select', $openOperation['context']['operation']);

        // Verify events were logged
        $this->assertCount(3, $logArray['events']); // 3 events logged

        // Verify result contains actual query data
        $this->assertNotNull($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame('1', $result['id']);
        $this->assertSame('Test Todo Item', $result['title']);

        // Verify semantic log structure includes result data
        $closeOperation = $logArray['close'];
        $this->assertSame('success', $closeOperation['context']['status']);

        // Verify relations are included
        $this->assertCount(2, $logArray['links']);
        $this->assertSame('related', $logArray['links'][0]['rel']);
    }

    public function testSemanticLogContextClasses(): void
    {
        // Test QueryContext
        $queryContext = new QueryContext(
            queryId: 'test_query',
            operation: 'select',
            sqlFile: 'test.sql',
            sqlContent: 'SELECT * FROM test',
        );

        $this->assertSame('query', $queryContext::TYPE);
        $this->assertSame('https://ray-di.github.io/Ray.MediaQuery/schemas/query.json', $queryContext::SCHEMA_URL);
        $this->assertSame('test_query', $queryContext->queryId);
        $this->assertSame('select', $queryContext->operation);

        // Test DatabaseContext
        $dbContext = new DatabaseContext(
            operation: 'execute',
            dsn: 'sqlite://test',
            executionTime: 0.05,
            affectedRows: 1,
        );

        $this->assertSame('database', $dbContext::TYPE);
        $this->assertSame('https://ray-di.github.io/Ray.MediaQuery/schemas/database.json', $dbContext::SCHEMA_URL);
        $this->assertSame('execute', $dbContext->operation);
        $this->assertSame(0.05, $dbContext->executionTime);

        // Test EntityContext
        $entityContext = new EntityContext(
            operation: 'hydrate',
            entityClass: 'TestEntity',
            fetchMethod: 'FetchClass',
            entityCount: 5,
            factoryClass: 'TestFactory',
        );

        $this->assertSame('entity', $entityContext::TYPE);
        $this->assertSame('https://ray-di.github.io/Ray.MediaQuery/schemas/entity.json', $entityContext::SCHEMA_URL);
        $this->assertSame('hydrate', $entityContext->operation);
        $this->assertSame('TestEntity', $entityContext->entityClass);
        $this->assertSame(5, $entityContext->entityCount);

        // Test EventContext
        $eventContext = new EventContext(
            message: 'Test event',
            level: 'info',
            data: ['test' => 'data'],
        );

        $this->assertSame('event', $eventContext::TYPE);
        $this->assertSame('https://ray-di.github.io/Ray.MediaQuery/schemas/event.json', $eventContext::SCHEMA_URL);
        $this->assertSame('Test event', $eventContext->message);
        $this->assertSame('info', $eventContext->level);

        // Test ResultContext
        $resultContext = new ResultContext(
            status: 'success',
            error: null,
            metadata: ['key' => 'value'],
            result: ['data' => 'test'],
            resultCount: 1,
        );

        $this->assertSame('result', $resultContext::TYPE);
        $this->assertSame('https://ray-di.github.io/Ray.MediaQuery/schemas/result.json', $resultContext::SCHEMA_URL);
        $this->assertSame('success', $resultContext->status);
        $this->assertSame(['data' => 'test'], $resultContext->result);
        $this->assertSame(1, $resultContext->resultCount);
    }

    public function testSemanticLogWithFailureScenario(): void
    {
        // OPEN: Start operation that will fail
        $queryId = $this->semanticLogger->open(new QueryContext(
            queryId: 'invalid_query',
            operation: 'select',
            sqlFile: 'nonexistent.sql',
        ));

        try {
            // Simulate failure
            throw new RuntimeException('Query execution failed');
        } catch (Throwable $e) {
            $this->semanticLogger->event(new EventContext(
                message: 'Query execution failed',
                level: 'error',
                data: ['error' => $e->getMessage()],
            ));

            $this->semanticLogger->close(new ResultContext(
                status: 'error',
                error: $e->getMessage(),
            ), $queryId);
        }

        $logJson = $this->semanticLogger->flush();
        $logArray = json_decode(json_encode($logJson), true);

        // Output to test tmp directory
        $testDir = __DIR__ . '/tmp/' . self::class;
        if (! is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $outputFile = $testDir . '/' . __FUNCTION__ . '.json';
        file_put_contents($outputFile, json_encode($logJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Verify error is properly logged
        $this->assertSame('error', $logArray['close']['context']['status']);
        $this->assertSame('Query execution failed', $logArray['close']['context']['error']);

        // Verify error event was logged
        $errorEvents = array_filter($logArray['events'], static fn ($event) => $event['context']['level'] === 'error');
        $this->assertCount(1, $errorEvents);
    }
}
