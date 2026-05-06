<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Queries\ArticlesInterface;
use Ray\MediaQuery\Result\Articles;

use function dirname;
use function file_get_contents;
use function iterator_to_array;

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

        $rows = $result->rows;

        // The `@return Articles<Article>` declaration carries `Article` through to
        // `$rows[0]`, so `->id` / `->title` are statically typed without a cast.
        $this->assertSame('1', $rows[0]->id);
        $this->assertSame('run', $rows[0]->title);
        $this->assertSame('2', $rows[1]->id);
        $this->assertSame('walk', $rows[1]->title);
    }

    public function testWrapperExposesCountableAndIteratorAggregate(): void
    {
        $repo = $this->injector->getInstance(ArticlesInterface::class);
        $result = $repo->listHydrated();

        $this->assertCount(2, $result);
        $this->assertFalse($result->isEmpty());

        // `iterator_to_array(Articles<Article>)` yields `array<int, Article>`.
        $iterated = iterator_to_array($result, false);
        $this->assertSame($result->rows, $iterated);
    }

    public function testFactoryAttributeHydratesViaPostQueryPath(): void
    {
        $repo = $this->injector->getInstance(ArticlesInterface::class);
        $result = $repo->listViaFactory();

        $this->assertInstanceOf(Articles::class, $result);
        $this->assertCount(2, $result->rows);

        $rows = $result->rows;

        // `@return Articles<TodoConstruct>` carries through here too.
        $this->assertSame('1', $rows[0]->id);
        $this->assertSame('run', $rows[0]->title);
        $this->assertSame('2', $rows[1]->id);
        $this->assertSame('walk', $rows[1]->title);
    }

    public function testEmptySelectStillReturnsArticlesWrapper(): void
    {
        $repo = $this->injector->getInstance(ArticlesInterface::class);
        $result = $repo->listEmpty();

        $this->assertInstanceOf(Articles::class, $result);
        $this->assertSame([], $result->rows);
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }
}
