<?php

declare(strict_types=1);

use Aura\Sql\ExtendedPdoInterface;
use Composer\Autoload\ClassLoader;
use Demo\User;
use Demo\UserItemInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;
use Ray\MediaQuery\SemanticLog\Context\DatabaseContext;
use Ray\MediaQuery\SemanticLog\Context\EntityContext;
use Ray\MediaQuery\SemanticLog\Context\EventContext;
use Ray\MediaQuery\SemanticLog\Context\QueryContext;
use Ray\MediaQuery\SemanticLog\Context\ResultContext;

/** @var ClassLoader $loader */
$loader = require dirname(__DIR__) . '/vendor/autoload.php';
$loader->addPsr4('Demo\\', __DIR__ . '/src');

// Demo with semantic logging integration
final class SemanticLogDemo
{
    private SemanticLogger $semanticLogger;
    
    public function __construct()
    {
        $this->semanticLogger = new SemanticLogger();
    }
    
    public function runDemo(): void
    {
        echo "=== Ray.MediaQuery Semantic Logging Demo ===\n\n";
        
        // OPEN: Start the overall demo operation
        $demoId = $this->semanticLogger->open(new QueryContext(
            queryId: 'demo_user_operations',
            operation: 'select',
            sqlFile: 'user_item.sql',
            sqlContent: 'SELECT * FROM users WHERE id = :id'
        ));
        
        try {
            // EVENT: Initialize Ray.MediaQuery
            $this->semanticLogger->event(new EventContext(
                message: 'Initializing Ray.MediaQuery framework',
                level: 'info',
                data: ['module' => 'MediaQueryModule']
            ));
            
            // Setup Ray.MediaQuery with SQLite in-memory database
            $sqlDir = __DIR__ . '/sql';
            $dsn = 'sqlite::memory:';
            $injector = new Injector(new class($sqlDir, $dsn) extends AbstractModule {
                public function __construct(
                    private string $sqlDir,
                    private string $dsn
                ){}

                protected function configure()
                {
                    $queries = Queries::fromClasses([
                        UserItemInterface::class
                    ]);
                    $this->install(new MediaQueryModule($queries, [new DbQueryConfig($this->sqlDir)]));
                    $this->install(new AuraSqlModule($this->dsn));
                }
            });
            
            // OPEN: Database setup operation
            $dbSetupId = $this->semanticLogger->open(new DatabaseContext(
                operation: 'setup',
                dsn: 'sqlite::memory:',
                executionTime: null,
                affectedRows: null
            ));
            
            // Setup database table
            /** @var ExtendedPdoInterface $pdo */
            $pdo = $injector->getInstance(ExtendedPdoInterface::class);
            $pdo->query('CREATE TABLE IF NOT EXISTS user (id TEXT, name TEXT)');
            $pdo->query("INSERT INTO user (id, name) VALUES ('1', 'Ray MediaQuery Demo User')");
            
            // Create the UserItemInterface instance
            $userItem = $injector->getInstance(UserItemInterface::class);
            
            $this->semanticLogger->event(new EventContext(
                message: 'Database setup completed',
                level: 'info'
            ));
            
            $this->semanticLogger->close(new ResultContext('success'), $dbSetupId);
            
            // OPEN: Query execution operation
            $queryId = $this->semanticLogger->open(new DatabaseContext(
                operation: 'execute',
                dsn: 'sqlite::memory:',
                executionTime: null,
                affectedRows: null
            ));
            
            $startTime = microtime(true);
            
            // EVENT: Executing query
            $this->semanticLogger->event(new EventContext(
                message: 'Executing user query',
                level: 'info',
                data: ['id' => 1]
            ));
            
            // Execute the actual query
            $user = $userItem('1');
            
            $executionTime = microtime(true) - $startTime;
            
            // Update execution time and close database operation
            $this->semanticLogger->close(new ResultContext(
                status: 'success',
                result: $user,
                resultCount: $user ? 1 : 0,
                metadata: ['execution_time' => $executionTime]
            ), $queryId);
            
            // OPEN: Entity processing
            $entityId = $this->semanticLogger->open(new EntityContext(
                operation: 'hydrate',
                entityClass: is_array($user) ? 'array' : get_class($user),
                fetchMethod: 'auto_detected',
                entityCount: 1
            ));
            
            $this->semanticLogger->event(new EventContext(
                message: 'User entity processed successfully',
                level: 'info',
                data: ['user_id' => is_array($user) ? ($user['id'] ?? null) : ($user->id ?? null)]
            ));
            
            $this->semanticLogger->close(new ResultContext('success'), $entityId);
            
            // Display the result
            echo "Query Result:\n";
            print_r($user);
            echo "\n";
            
        } catch (Exception $e) {
            $this->semanticLogger->event(new EventContext(
                message: 'Error during demo execution',
                level: 'error',
                data: ['error' => $e->getMessage()]
            ));
            
            $this->semanticLogger->close(new ResultContext(
                status: 'error',
                error: $e->getMessage()
            ), $demoId);
            
            echo "Error: " . $e->getMessage() . "\n";
            return;
        }
        
        // CLOSE: Complete demo operation
        $this->semanticLogger->close(new ResultContext(
            status: 'success',
            metadata: ['demo_completed' => true]
        ), $demoId);
        
        // Generate semantic log with relations
        $relations = [
            ['rel' => 'related', 'href' => 'https://github.com/ray-di/Ray.MediaQuery', 'title' => 'Ray.MediaQuery Repository'],
            ['rel' => 'describedby', 'href' => 'https://ray-di.github.io/Ray.MediaQuery/demo/sql/user_item.sql', 'title' => 'SQL Schema'],
            ['rel' => 'documentation', 'href' => 'https://ray-di.github.io/Ray.MediaQuery/src/SemanticLog/README.md', 'title' => 'Semantic Logging Documentation']
        ];
        
        $logJson = $this->semanticLogger->flush($relations);
        
        // Output the semantic log
        echo "=== Semantic Log Output ===\n";
        echo json_encode($logJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n";
    }
}

// Run the demo
$demo = new SemanticLogDemo();
$demo->runDemo();