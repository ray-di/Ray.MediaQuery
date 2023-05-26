<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Entity\Memo;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Exception\InvalidPerPageVarNameException;
use Ray\MediaQuery\Exception\PerPageNotIntTypeException;
use Ray\MediaQuery\Factory\FakeFactoryHelper;
use Ray\MediaQuery\Factory\FakeFactoryHelperInterface;
use Ray\MediaQuery\Fake\Queries\TodoEntityNullableInterface;
use Ray\MediaQuery\Fake\Queries\TodoFactoryInterface;
use Ray\MediaQuery\Fake\Queries\TodoFactoryUnionInterface;
use Ray\MediaQuery\Queries\DynamicPerPageInterface;
use Ray\MediaQuery\Queries\DynamicPerPageInvalidInterface;
use Ray\MediaQuery\Queries\DynamicPerPageInvalidType;
use Ray\MediaQuery\Queries\PagerEntityInterface;
use Ray\MediaQuery\Queries\PromiseAddInterface;
use Ray\MediaQuery\Queries\PromiseItemInterface;
use Ray\MediaQuery\Queries\PromiseListInterface;
use Ray\MediaQuery\Queries\TodoAddInterface;
use Ray\MediaQuery\Queries\TodoConstcuctEntityInterface;
use Ray\MediaQuery\Queries\TodoEntityInterface;
use Ray\MediaQuery\Queries\TodoItemInterface;
use Ray\MediaQuery\Queries\TodoListInterface;

use function array_keys;
use function assert;
use function dirname;
use function file_get_contents;
use function is_array;
use function is_callable;

class DbQueryModuleTest extends TestCase
{
    protected AbstractModule $module;
    private MediaQueryLoggerInterface $logger;
    private Injector $injector;
    private string $sqlDir;
    private ExtendedPdoInterface $pdo;

    protected function setUp(): void
    {
        $mediaQueries = Queries::fromClasses([
            TodoAddInterface::class,
            TodoItemInterface::class,
            TodoListInterface::class,
            PromiseAddInterface::class,
            PromiseItemInterface::class,
            PromiseListInterface::class,
            TodoEntityInterface::class,
            TodoEntityNullableInterface::class,
            TodoConstcuctEntityInterface::class,
            DynamicPerPageInterface::class,
            DynamicPerPageInvalidInterface::class,
            DynamicPerPageInvalidType::class,
            PagerEntityInterface::class,
            TodoFactoryInterface::class,
            TodoFactoryUnionInterface::class,
        ]);
        $this->sqlDir = $sqlDir = dirname(__DIR__) . '/tests/sql';
        $dbQueryConfig = new DbQueryConfig($sqlDir);
        $module = new MediaQueryModule($mediaQueries, [$dbQueryConfig], new AuraSqlModule('sqlite::memory:', '', '', '', [PDO::ATTR_STRINGIFY_FETCHES => true])); /* @phpstan-ignore-line */
        $module->install(new class extends AbstractModule{
            protected function configure(): void
            {
                $this->bind(FakeFactoryHelperInterface::class)->to(FakeFactoryHelper::class);
            }
        });
        $this->injector = new Injector($module, __DIR__ . '/tmp');
        $this->pdo = $pdo = $this->injector->getInstance(ExtendedPdoInterface::class);
        assert($pdo instanceof ExtendedPdoInterface);
        $pdo->query((string) file_get_contents($sqlDir . '/create_todo.sql'));
        $pdo->query((string) file_get_contents($sqlDir . '/create_promise.sql'));
        $pdo->query((string) file_get_contents($sqlDir . '/create_memo.sql'));
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), ['id' => '1', 'title' => 'run']);
        $pdo->perform((string) file_get_contents($sqlDir . '/promise_add.sql'), ['id' => '1', 'title' => 'run', 'time' => UnixEpocTime::TEXT]);
        $pdo->perform((string) file_get_contents($sqlDir . '/memo_add.sql'), ['id' => '1', 'body' => 'memo1', 'todoId' => '1']);
        $pdo->perform((string) file_get_contents($sqlDir . '/memo_add.sql'), ['id' => '2', 'body' => 'memo2', 'todoId' => '1']);
        /** @var MediaQueryLoggerInterface $logger */
        $logger = $this->injector->getInstance(MediaQueryLoggerInterface::class);
        $this->logger = $logger;
    }

    public function testInsertItem(): void
    {
        $todoAdd = $this->injector->getInstance(TodoAddInterface::class);
        assert($todoAdd instanceof TodoAddInterface);
        $todoAdd('1', 'run');
        $log = (string) $this->logger;
        $this->assertStringContainsString('query: todo_add', $log);
        $todoItem = $this->injector->getInstance(TodoItemInterface::class);

        assert($todoItem instanceof TodoItemInterface);
        $item = $todoItem('1');
        $this->assertSame(['id' => '1', 'title' => 'run'], $item);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query: todo_item', $log);
    }

    public function testSelectItem(): void
    {
        $todoItem = $this->injector->getInstance(TodoItemInterface::class);
        assert($todoItem instanceof TodoItemInterface);
        $item = $todoItem('1');
        $this->assertSame(['id' => '1', 'title' => 'run'], $item);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query: todo_item', $log);
    }

    public function testSelectList(): void
    {
        $promiselist = $this->injector->getInstance(PromiseListInterface::class);
        assert($promiselist instanceof PromiseListInterface);
        $list = $promiselist->get();
        $row = ['id' => '1', 'title' => 'run', 'time' => '1970-01-01 00:00:00'];
        $this->assertSame([$row], $list);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query: promise_list([])', $log);
    }

    public function testSelectPager(): void
    {
        $todoList = $this->injector->getInstance(TodoListInterface::class);
        assert($todoList instanceof TodoListInterface);
        $list = ($todoList)();
        /** @var Page $page */
        $page = $list[1];
        $this->assertSame([['id' => '1', 'title' => 'run']], $page->data);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query: todo_list', $log);
    }

    public function testPramInjection(): void
    {
        /** @var FakeFoo $foo */
        $foo = $this->injector->getInstance(FakeFoo::class);
        $foo->add();
        $item = $foo->get();
        $this->assertSame(['id', 'title', 'time'], array_keys($item));
    }

    public function testEntity(): void
    {
        /** @var TodoEntityInterface $todoList */
        $todoList = $this->injector->getInstance(TodoEntityInterface::class);
        $list = $todoList->getList();
        $this->assertInstanceOf(Todo::class, $list[0]);
        $this->assertSame('run', $list[0]->title);
        $item = $todoList->getItem('1');
        $this->assertInstanceOf(Todo::class, $item);
    }

    public function testEntityNullable(): void
    {
        /** @var TodoEntityInterface $todoList */
        $todoList = $this->injector->getInstance(TodoEntityNullableInterface::class);
        $list = $todoList->getList();
        $this->assertInstanceOf(Todo::class, $list[0]);
        $this->assertSame('run', $list[0]->title);
        $item = $todoList->getItem('1');
        $this->assertInstanceOf(Todo::class, $item);
    }

    public function testEntityWithConstructor(): void
    {
        /** @var TodoEntityInterface $todoList */
        $todoList = $this->injector->getInstance(TodoConstcuctEntityInterface::class);
        $list = $todoList->getList();
        $this->assertInstanceOf(TodoConstruct::class, $list[0]);
        $this->assertSame('run', $list[0]->title);
        $item = $todoList->getItem('1');
        $this->assertInstanceOf(TodoConstruct::class, $item);
    }

    public function testDynamicPerPage(): void
    {
        $todoList = $this->injector->getInstance(DynamicPerPageInterface::class);
        assert($todoList instanceof DynamicPerPageInterface);
        $list = $todoList->get(2);
        /** @var Page $page */
        $page = $list[1];
        $this->assertSame([['id' => '1', 'title' => 'run']], $page->data);
        $this->assertSame(2, $page->maxPerPage);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query: todo_list', $log);
    }

    public function testDynamicPerPageWithParameterInjection(): void
    {
        $todoList = $this->injector->getInstance(DynamicPerPageInterface::class);
        assert($todoList instanceof DynamicPerPageInterface);

        $list = $todoList->getWithScalarParam(2);
        /** @var Page $page */
        $page = $list[1];
        $this->assertSame([['id' => '1', 'title' => 'run']], $page->data);
        $this->assertSame(2, $page->maxPerPage);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query: todo_list', $log);

        $list = $todoList->getWithFakeStringParam(2);
        /** @var Page $page */
        $page = $list[1];
        $this->assertSame([['id' => '1', 'title' => 'run']], $page->data);
        $this->assertSame(2, $page->maxPerPage);

        $list = $todoList->getWithFakeBoolParam(2);
        /** @var Page $page */
        $page = $list[1];
        $this->assertSame([['id' => '1', 'title' => 'run']], $page->data);
        $this->assertSame(2, $page->maxPerPage);
    }

    public function testDynamicPerPageVariableNameNotGiven(): void
    {
        $this->expectException(InvalidPerPageVarNameException::class);
        $todoList = $this->injector->getInstance(DynamicPerPageInvalidInterface::class);
        assert($todoList instanceof DynamicPerPageInvalidInterface);
        ($todoList)(1);
    }

    public function testGivenPerPageShouldBeInt(): void
    {
        $this->expectException(PerPageNotIntTypeException::class);
        $todoList = $this->injector->getInstance(DynamicPerPageInvalidType::class);
        assert(is_callable($todoList));
        $todoList('1');
    }

    public function testSelectPagerEntity(): void
    {
        $todoList = $this->injector->getInstance(PagerEntityInterface::class);
        assert($todoList instanceof PagerEntityInterface);
        $list = ($todoList)();
        $page = $list[1];
        assert($page instanceof Page);
        assert(is_array($page->data));
        $this->assertInstanceOf(TodoConstruct::class, $page->data[0]);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query: todo_list', $log);
    }

    /** @return array<array<class-string>> */
    public function queryInterfaceProvider(): array
    {
        return [
            [TodoFactoryInterface::class],
            [TodoFactoryUnionInterface::class],
        ];
    }

    /**
     * @param class-string $queryInterface
     *
     * @dataProvider queryInterfaceProvider
     */
    public function testStaticFactory(string $queryInterface): void
    {
        /** @var TodoFactoryInterface|TodoFactoryUnionInterface $todoList */
        $todoList = $this->injector->getInstance($queryInterface);
        $list = $todoList->getList();
        $this->assertInstanceOf(TodoConstruct::class, $list[0]);
        $this->assertSame('run', $list[0]->title);
        $item = $todoList->getItem('1');
        $this->assertInstanceOf(TodoConstruct::class, $item);
    }

    public function testFactoryInjection(): void
    {
        $todoQuery = $this->injector->getInstance(TodoFactoryInterface::class);
        $todoList = $todoQuery->getListInjection();
        $this->assertSame('RUN', $todoList[0]->title);
    }

    /**
     * 1 対 多 のリレーション検証をします。
     * Todo -< Memo のリレーションがあります。
     */
    public function testOneToMany(): void
    {
        $this->pdo->perform((string) file_get_contents($this->sqlDir . '/todo_add.sql'), ['id' => '2', 'title' => 'walk']);
        $query = $this->injector->getInstance(TodoEntityInterface::class);
        assert($query instanceof TodoEntityInterface);
        $todos = $query->getListWithMemo('1');
        $this->assertNotEmpty($todos[0]->memos);
        $this->assertEmpty($todos[1]->memos);
        $this->assertContainsOnlyInstancesOf(
            className: Memo::class,
            haystack: $todos[0]->memos,
        );
        $this->assertCount(expectedCount: 2, haystack: $todos[0]->memos);
    }
}
