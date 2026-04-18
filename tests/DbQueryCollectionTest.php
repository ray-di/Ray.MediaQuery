<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Factory\FakeFactoryHelper;
use Ray\MediaQuery\Factory\FakeFactoryHelperInterface;
use Ray\MediaQuery\Fake\Queries\TodoCollectionInterface;

use function dirname;
use function file_get_contents;

class DbQueryCollectionTest extends TestCase
{
    private Injector $injector;

    protected function setUp(): void
    {
        $mediaQueries = Queries::fromClasses([
            TodoCollectionInterface::class,
        ]);
        $sqlDir = dirname(__DIR__) . '/tests/sql';
        $dbQueryConfig = new DbQueryConfig($sqlDir);
        $module = new MediaQueryModule(
            $mediaQueries,
            [$dbQueryConfig],
            new AuraSqlModule(
                'sqlite::memory:',
                '',
                '',
                '',
                [PDO::ATTR_STRINGIFY_FETCHES => true], // @phpstan-ignore-line
            ),
        );
        $module->install(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeFactoryHelperInterface::class)->to(FakeFactoryHelper::class);
            }
        });
        $this->injector = new Injector($module, __DIR__ . '/tmp');
        $pdo = $this->injector->getInstance(ExtendedPdoInterface::class);
        $pdo->query((string) file_get_contents($sqlDir . '/create_todo.sql'));
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), ['id' => '1', 'title' => 'run']);
    }

    public function testListReturnsCollectionOfAssocArrays(): void
    {
        $repo = $this->injector->getInstance(TodoCollectionInterface::class);
        $result = $repo->list();
        $this->assertInstanceOf(FakeTodoCollection::class, $result);
        $items = $result->items;
        $this->assertIsArray($items[0]);
        $this->assertSame('1', $items[0]['id']);
        $this->assertSame('run', $items[0]['title']);
    }

    public function testHydratedReturnsCollectionOfEntities(): void
    {
        $repo = $this->injector->getInstance(TodoCollectionInterface::class);
        $result = $repo->hydrated();
        $this->assertInstanceOf(FakeTodoCollection::class, $result);
        $this->assertInstanceOf(TodoConstruct::class, $result->items[0]);
        $this->assertSame('run', $result->items[0]->title);
    }

    public function testDocblockEntityReturnsCollectionOfTodo(): void
    {
        $repo = $this->injector->getInstance(TodoCollectionInterface::class);
        $result = $repo->docblockEntity();
        $this->assertInstanceOf(FakeTodoCollection::class, $result);
        $this->assertInstanceOf(Todo::class, $result->items[0]);
        $this->assertSame('run', $result->items[0]->title);
    }

    public function testUntypedCollectionConstructedSuccessfully(): void
    {
        $repo = $this->injector->getInstance(TodoCollectionInterface::class);
        $result = $repo->untyped();
        $this->assertInstanceOf(FakeUntypedCollection::class, $result);
        $this->assertNotEmpty($result->items);
    }

    public function testHydratedCollectionIsCountableAndTraversable(): void
    {
        $repo = $this->injector->getInstance(TodoCollectionInterface::class);
        $result = $repo->hydrated();
        $this->assertCount(1, $result);
        $items = [];
        foreach ($result as $item) {
            $items[] = $item;
        }

        $this->assertCount(1, $items);
        $this->assertInstanceOf(TodoConstruct::class, $items[0]);
    }
}
