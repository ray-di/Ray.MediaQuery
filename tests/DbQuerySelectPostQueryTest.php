<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Entity\Article;
use Ray\MediaQuery\Queries\ArticlesInterface;
use Ray\MediaQuery\Result\Articles;

use function dirname;
use function file_get_contents;

class DbQuerySelectPostQueryTest extends TestCase
{
    private Injector $injector;

    protected function setUp(): void
    {
        $mediaQueries = Queries::fromClasses([
            ArticlesInterface::class,
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
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), ['id' => '1', 'title' => 'run']);
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), ['id' => '2', 'title' => 'walk']);
    }

    public function testReturnsArticlesWrapperWithAssocRows(): void
    {
        $repo = $this->injector->getInstance(ArticlesInterface::class);
        $result = $repo->listAssoc();

        $this->assertInstanceOf(Articles::class, $result);
        $this->assertSame(
            [
                ['id' => '1', 'title' => 'run'],
                ['id' => '2', 'title' => 'walk'],
            ],
            $result->rows,
        );
    }

    public function testReturnsArticlesWrapperWithHydratedEntities(): void
    {
        $repo = $this->injector->getInstance(ArticlesInterface::class);
        $result = $repo->listHydrated();

        $this->assertInstanceOf(Articles::class, $result);
        $this->assertCount(2, $result->rows);

        [$first, $second] = [$result->rows[0], $result->rows[1]];

        $this->assertInstanceOf(Article::class, $first);
        $this->assertSame('1', $first->id);
        $this->assertSame('run', $first->title);

        $this->assertInstanceOf(Article::class, $second);
        $this->assertSame('2', $second->id);
        $this->assertSame('walk', $second->title);
    }
}
