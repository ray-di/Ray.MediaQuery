<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Aop\TodoAdd;
use Ray\MediaQuery\Aop\TodoItem;
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
    private $insertItem = ['id' => '1', 'title' => 'run'];

    protected function setUp(): void
    {
        $module = new MediaQueryModule(dirname(__DIR__) . '/tests/sql', new AuraSqlModule('sqlite::memory:'));
        $module->install(new class extends AbstractModule{
            protected function configure(): void
            {
                $this->bind(TodoAddInterface::class)->to(TodoAdd::class);
                $this->bind(TodoItemInterface::class)->to(TodoItem::class);
            }
        });
        $this->injector = new Injector($module);
        $pdo = $this->injector->getInstance(ExtendedPdoInterface::class);
        assert($pdo instanceof ExtendedPdoInterface);
        $pdo->query(/** @lang sql */'CREATE TABLE IF NOT EXISTS todo (
          id INTEGER,
          title TEXT
)');
        $pdo->perform(/** @lang sql */'INSERT INTO todo (id, title) VALUES (:id, :title)', ['id' => '1', 'title' => 'run']);
        /** @var MediaQueryLoggerInterface logger */
        $this->logger = $this->injector->getInstance(MediaQueryLoggerInterface::class);
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
}
