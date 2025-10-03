<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\SemanticLog\Context\DatabaseContext;
use Ray\MediaQuery\SemanticLog\Context\EntityContext;
use Ray\MediaQuery\SemanticLog\Context\EventContext;
use Ray\MediaQuery\SemanticLog\Context\QueryContext;
use Ray\MediaQuery\SemanticLog\Context\ResultContext;

use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class SemanticLogSchemaValidationTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array{valid: bool, errors: array<string>, schema: mixed}
     */
    private function validateJsonSchema(array $data, string $schemaFile): array
    {
        $schemaPath = __DIR__ . '/../docs/schemas/' . $schemaFile;
        $this->assertFileExists($schemaPath, "Schema file {$schemaFile} should exist");

        $schemaContent = file_get_contents($schemaPath);
        $this->assertNotFalse($schemaContent, "Schema file {$schemaFile} should be readable");

        $schema = json_decode($schemaContent);
        $this->assertNotNull($schema, "Schema {$schemaFile} should be valid JSON");

        // Convert data to object for validation
        $dataObject = json_decode(json_encode($data));

        // Validate using JSON Schema library
        $validator = new Validator();
        $validator->validate($dataObject, $schema, Constraint::CHECK_MODE_APPLY_DEFAULTS);

        $errors = [];
        if (! $validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $errors[] = sprintf('[%s] %s', $error['property'], $error['message']);
            }
        }

        return [
            'valid' => $validator->isValid(),
            'errors' => $errors,
            'schema' => $schema,
        ];
    }

    public function testQueryContextSchemaValidation(): void
    {
        $context = new QueryContext(
            queryId: 'test_query',
            operation: 'select',
            sqlFile: 'test.sql',
            sqlContent: 'SELECT * FROM test WHERE id = :id',
        );

        // Convert context to array for validation
        $contextData = (array) $context;

        // Validate against schema using proper JSON Schema library
        $validation = $this->validateJsonSchema($contextData, 'query.json');
        $this->assertTrue($validation['valid'], 'Validation errors: ' . implode(', ', $validation['errors']));

        // Output context data to test tmp directory
        $testDir = __DIR__ . '/tmp';
        if (! is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $outputFile = $testDir . '/' . basename(self::class) . '.json';
        file_put_contents($outputFile, json_encode($contextData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Validate that schema validation succeeded
        $this->assertTrue($validation['valid']);
    }

    public function testDatabaseContextSchemaValidation(): void
    {
        $context = new DatabaseContext(
            operation: 'execute',
            dsn: 'sqlite::memory:',
            executionTime: 0.025,
            affectedRows: 5,
        );

        $contextData = (array) $context;
        $validation = $this->validateJsonSchema($contextData, 'database.json');
        $this->assertTrue($validation['valid'], 'Validation errors: ' . implode(', ', $validation['errors']));

        // Validate that schema validation succeeded
        $this->assertTrue($validation['valid']);
    }

    public function testEntityContextSchemaValidation(): void
    {
        $context = new EntityContext(
            operation: 'hydrate',
            entityClass: 'App\\Entity\\User',
            fetchMethod: 'FetchClass',
            entityCount: 10,
            factoryClass: 'App\\Factory\\UserFactory',
        );

        $contextData = (array) $context;
        $validation = $this->validateJsonSchema($contextData, 'entity.json');
        $this->assertTrue($validation['valid'], 'Validation errors: ' . implode(', ', $validation['errors']));

        // Validate that schema validation succeeded
        $this->assertTrue($validation['valid']);
    }

    public function testEventContextSchemaValidation(): void
    {
        $context = new EventContext(
            message: 'Test event message',
            level: 'info',
            data: ['key' => 'value', 'count' => 42],
        );

        $contextData = (array) $context;
        $validation = $this->validateJsonSchema($contextData, 'event.json');
        $this->assertTrue($validation['valid'], 'Validation errors: ' . implode(', ', $validation['errors']));

        // Validate that schema validation succeeded
        $this->assertTrue($validation['valid']);
    }

    public function testResultContextSchemaValidation(): void
    {
        $context = new ResultContext(
            status: 'success',
            error: null,
            metadata: ['execution_time' => 0.05, 'memory_usage' => 1024],
            result: ['id' => 1, 'name' => 'Test'],
            resultCount: 1,
        );

        $contextData = (array) $context;
        $validation = $this->validateJsonSchema($contextData, 'result.json');
        $this->assertTrue($validation['valid'], 'Validation errors: ' . implode(', ', $validation['errors']));

        // Validate that schema validation succeeded
        $this->assertTrue($validation['valid']);
    }

    public function testAllSchemasHaveValidJsonStructure(): void
    {
        $schemaFiles = [
            'query.json',
            'database.json',
            'entity.json',
            'event.json',
            'result.json',
        ];

        foreach ($schemaFiles as $schemaFile) {
            $schemaPath = __DIR__ . '/../docs/schemas/' . $schemaFile;
            $this->assertFileExists($schemaPath, "Schema file {$schemaFile} should exist");

            $content = file_get_contents($schemaPath);
            $this->assertNotFalse($content, "Schema file {$schemaFile} should be readable");

            $schema = json_decode($content);
            $this->assertNotNull($schema, "Schema {$schemaFile} should be valid JSON");
        }
    }

    public function testSchemaUrlsMatchActualFiles(): void
    {
        $contexts = [
            QueryContext::class => 'query.json',
            DatabaseContext::class => 'database.json',
            EntityContext::class => 'entity.json',
            EventContext::class => 'event.json',
            ResultContext::class => 'result.json',
        ];

        foreach ($contexts as $contextClass => $expectedSchemaFile) {
            $expectedUrl = "https://ray-di.github.io/Ray.MediaQuery/schemas/{$expectedSchemaFile}";
            $this->assertSame(
                $expectedUrl,
                $contextClass::SCHEMA_URL,
                "Schema URL for {$contextClass} should match expected URL",
            );
        }
    }

    public function testSchemaValidationWithInvalidData(): void
    {
        // Test with missing required field
        $validation = $this->validateJsonSchema(
            ['operation' => 'select'], // missing required 'queryId'
            'query.json',
        );
        $this->assertFalse($validation['valid'], 'Validation should fail for missing required field');
        $this->assertNotEmpty($validation['errors'], 'Should have validation errors');

        // Test with invalid operation value (not in enum)
        $invalidData = [
            'queryId' => 'test',
            'operation' => 'invalid_operation', // not in enum
        ];

        $validation = $this->validateJsonSchema($invalidData, 'query.json');
        $this->assertFalse($validation['valid'], 'Validation should fail for invalid enum value');
        $this->assertNotEmpty($validation['errors'], 'Should have validation errors for enum');
    }
}
