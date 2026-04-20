<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Queries\TodoCustomPostQueryInterface;
use Ray\MediaQuery\Result\RowCountWithQuery;

use function dirname;
use function file_get_contents;

class DbQueryCustomPostQueryTest extends TestCase
{
    public function testUserDefinedPostQueryIsDispatched(): void
    {
        $mediaQueries = Queries::fromClasses([
            TodoCustomPostQueryInterface::class,
        ]);
        $sqlDir = dirname(__DIR__) . '/tests/sql';
        $dbQueryConfig = new DbQueryConfig($sqlDir);
        $module = new MediaQueryModule(
            $mediaQueries,
            [$dbQueryConfig],
            new AuraSqlModule('sqlite::memory:', '', '', '', [PDO::ATTR_STRINGIFY_FETCHES => true]), // @phpstan-ignore-line
        );
        $injector = new Injector($module, __DIR__ . '/tmp');
        $pdo = $injector->getInstance(ExtendedPdoInterface::class);
        $pdo->query((string) file_get_contents($sqlDir . '/create_todo.sql'));
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), ['id' => '1', 'title' => 'run']);

        $repo = $injector->getInstance(TodoCustomPostQueryInterface::class);
        $result = $repo->delete('1');

        $this->assertInstanceOf(RowCountWithQuery::class, $result);
        $this->assertSame(1, $result->count);
        $this->assertStringContainsStringIgnoringCase('delete', $result->queryString);
    }
}
