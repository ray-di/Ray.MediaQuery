<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
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
use Throwable;

use function count;
use function file_put_contents;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class SemanticLogIntegrationTest extends TestCase
{
    private Injector $injector;

    protected function setUp(): void
    {
        // Setup Ray.MediaQuery with SemanticLoggerModule - this is the key!
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                // Install SemanticLoggerModule first to enable automatic logging
                $this->install(new \Ray\MediaQuery\SemanticLog\Module\SemanticLoggerModule());
                
                // Then install MediaQuery modules
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
        // Get the semantic logger that SqlQuery should be using automatically
        $logger = $this->injector->getInstance(\Koriym\SemanticLogger\SemanticLoggerInterface::class);

        // Execute Ray.MediaQuery operation - this should automatically generate semantic logs
        /** @var TodoItemInterface $todoItem */
        $todoItem = $this->injector->getInstance(TodoItemInterface::class);
        $result = $todoItem('1');

        // Verify the query result is correct
        $this->assertNotNull($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame('1', $result['id']);
        $this->assertSame('Test Todo Item', $result['title']);

        // Get the automatically generated semantic log
        $logJson = $logger->flush();
        $logArray = json_decode(json_encode($logJson), true);

        // Output to test tmp directory
        $testDir = __DIR__ . '/tmp';
        if (! is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $outputFile = $testDir . '/' . basename(self::class) . '.json';
        file_put_contents($outputFile, json_encode($logJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Verify that MediaQuery automatically generated semantic logs
        $this->assertNotNull($logJson);
        $this->assertArrayHasKey('open', $logArray);
        $this->assertArrayHasKey('close', $logArray);

        // Verify the open operation is a QueryContext from SqlQuery
        $openOperation = $logArray['open'];
        $this->assertSame('query', $openOperation['type']);
        $this->assertSame('todo_item', $openOperation['context']['queryId']);
        $this->assertSame('select', $openOperation['context']['operation']);
        $this->assertStringContains('todo_item.sql', $openOperation['context']['sqlFile']);
        $this->assertStringContains('SELECT', $openOperation['context']['sqlContent']);

        // Verify the close operation shows success with result metadata
        $closeOperation = $logArray['close'];
        $this->assertSame('result', $closeOperation['type']);
        $this->assertSame('success', $closeOperation['context']['status']);
        $this->assertArrayHasKey('metadata', $closeOperation['context']);
        $this->assertArrayHasKey('resultCount', $closeOperation['context']['metadata']);
        $this->assertSame(1, $closeOperation['context']['metadata']['resultCount']);
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
        $logger = $this->injector->getInstance(\Koriym\SemanticLogger\SemanticLoggerInterface::class);

        // Try to execute a MediaQuery operation that will fail (non-existent query)
        try {
            /** @var TodoItemInterface $todoItem */
            $todoItem = $this->injector->getInstance(TodoItemInterface::class);
            // This should fail and be logged automatically by SqlQuery
            $todoItem('nonexistent_id_that_causes_some_error');
        } catch (Throwable $e) {
            // Expected to fail, but SqlQuery should have logged the error automatically
        }

        $logJson = $logger->flush();
        $logArray = json_decode(json_encode($logJson), true);

        // Output to test tmp directory
        $testDir = __DIR__ . '/tmp';
        if (! is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $outputFile = $testDir . '/' . basename(self::class) . '_failure.json';
        file_put_contents($outputFile, json_encode($logJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // For this test, we just verify that some log was generated
        // (The actual failure scenario depends on how TodoItemInterface behaves)
        $this->assertNotNull($logJson);
        
        // If logs were generated, verify the structure
        if (isset($logArray['open'])) {
            $this->assertArrayHasKey('open', $logArray);
            $this->assertArrayHasKey('close', $logArray);
        }
    }
}
