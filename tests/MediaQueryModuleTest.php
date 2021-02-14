<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Queries\PromiseAddInterface;
use Ray\MediaQuery\Queries\PromiseItemInterface;
use Ray\MediaQuery\Queries\TodoAddInterface;
use Ray\MediaQuery\Queries\TodoItemInterface;
use Ray\MediaQuery\Queries\TodoListInterface;
use function array_keys;
use function assert;
use function dirname;

class MediaQueryModuleTest extends TestCase
{
    /** @var AbstractModule */
    protected $module;

    /** @var MediaQueryLoggerInterface */
    private $logger;

    /** @var Injector */
    private $injector;

    protected function setUp(): void
    {
        $mediaQueries = Queries::fromClasses([
            TodoAddInterface::class,
            TodoItemInterface::class,
            TodoListInterface::class,
            PromiseAddInterface::class,
            PromiseItemInterface::class,
        ]);
        $module = new MediaQueryModule(dirname(__DIR__) . '/tests/sql', $mediaQueries, new AuraSqlModule('sqlite::memory:'));
        $this->injector = new Injector($module);
        $pdo = $this->injector->getInstance(ExtendedPdoInterface::class);
        assert($pdo instanceof ExtendedPdoInterface);
        $pdo->query(/** @lang sql */'CREATE TABLE IF NOT EXISTS todo (
          id INTEGER,
          title TEXT
)');
        $pdo->query(/** @lang sql */'CREATE TABLE IF NOT EXISTS promise (
          id TEXT,
          title TEXT,
          time TEXT
)');
        $pdo->perform(/** @lang sql */'INSERT INTO todo (id, title) VALUES (:id, :title)', ['id' => '1', 'title' => 'run']);
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
        $this->assertStringContainsString('query:todo_add', $log);
        $todoItem = $this->injector->getInstance(TodoItemInterface::class);

        assert($todoItem instanceof TodoItemInterface);
        $item = $todoItem('1');
        $this->assertSame(['id' => '1', 'title' => 'run'], $item);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query:todo_item', $log);
    }

    public function testSelectItem(): void
    {
        $todoItem = $this->injector->getInstance(TodoItemInterface::class);
        assert($todoItem instanceof TodoItemInterface);
        $item = $todoItem('1');
        $this->assertSame(['id' => '1', 'title' => 'run'], $item);
        $log = (string) $this->logger;
        $this->assertStringContainsString('query:todo_item', $log);
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
        $this->assertStringContainsString('query:todo_list', $log);
    }

    public function testPramInjection(): void
    {
        /** @var FakeFoo $foo */
        $foo = $this->injector->getInstance(FakeFoo::class);
        $foo->add();
        $item = $foo->get();
        $this->assertSame(['id', 'title', 'time'], array_keys($item));
    }
}
