<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Queries\TodoAffectedInterface;
use Ray\MediaQuery\Result\AffectedRows;

use function dirname;
use function file_get_contents;

class DbQueryAffectedRowsTest extends TestCase
{
    private Injector $injector;

    protected function setUp(): void
    {
        $mediaQueries = Queries::fromClasses([
            TodoAffectedInterface::class,
        ]);
        $sqlDir = dirname(__DIR__) . '/tests/sql';
        $dbQueryConfig = new DbQueryConfig($sqlDir);
        $module = new MediaQueryModule(
            $mediaQueries,
            [$dbQueryConfig],
            new AuraSqlModule('sqlite::memory:', '', '', '', [PDO::ATTR_STRINGIFY_FETCHES => true]), // @phpstan-ignore-line
        );
        $this->injector = new Injector($module, __DIR__ . '/tmp');
        $pdo = $this->injector->getInstance(ExtendedPdoInterface::class);
        $pdo->query((string) file_get_contents($sqlDir . '/create_todo.sql'));
        $pdo->query((string) file_get_contents($sqlDir . '/create_counter.sql'));
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), ['id' => '1', 'title' => 'run']);
    }

    public function testDeleteExisting(): void
    {
        $repo = $this->injector->getInstance(TodoAffectedInterface::class);
        $result = $repo->delete('1');
        $this->assertInstanceOf(AffectedRows::class, $result);
        $this->assertSame(1, $result->count);
        $this->assertTrue($result->isAffected());
        $this->assertNull($result->lastInsertId);
    }

    public function testDeleteMissing(): void
    {
        $repo = $this->injector->getInstance(TodoAffectedInterface::class);
        $result = $repo->delete('__missing__');
        $this->assertSame(0, $result->count);
        $this->assertFalse($result->isAffected());
        $this->assertNull($result->lastInsertId);
    }

    public function testUpdate(): void
    {
        $repo = $this->injector->getInstance(TodoAffectedInterface::class);
        $result = $repo->update('1', 'walk');
        $this->assertSame(1, $result->count);
        $this->assertNull($result->lastInsertId);
    }

    public function testInsertReturnsLastInsertId(): void
    {
        $repo = $this->injector->getInstance(TodoAffectedInterface::class);
        $first = $repo->addCounter('alpha');
        $this->assertSame(1, $first->count);
        $this->assertSame('1', $first->lastInsertId);

        $second = $repo->addCounter('beta');
        $this->assertSame(1, $second->count);
        $this->assertSame('2', $second->lastInsertId);
    }
}
