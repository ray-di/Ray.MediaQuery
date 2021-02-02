<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\Aop\TodoAdd;
use Ray\MediaQuery\Aop\TodoItem;

use function assert;
use function dirname;

class MediaQueryTest extends TestCase
{
    /** @var AbstractModule */
    protected $module;

    /** @var MediaQueryLoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->module = new MediaQueryModule(dirname(__DIR__) . '/tests/sql');
        $this->module->install(new class extends AbstractModule{
            protected function configure(): void
            {
                $pdo = new ExtendedPdo('sqlite::memory:');
                $pdo->query(/** @lang sql */'CREATE TABLE IF NOT EXISTS todo (
          id INTEGER,
          title TEXT
)');
                $pdo->perform(/** @lang sql */'INSERT INTO todo (id, title) VALUES (:id, :title)', ['id' => '1', 'title' => 'run']);

                $this->bind(ExtendedPdoInterface::class)->toInstance($pdo);
                $this->bind(TodoAddInterface::class)->to(TodoAdd::class);
                $this->bind(TodoItemInterface::class)->to(TodoItem::class);
            }
        });
        $injector = new Injector($this->module);
        $this->logger = $injector->getInstance(MediaQueryLoggerInterface::class);
        assert($this->logger instanceof MediaQueryLogger);
    }

    public function testInsertItem(): void
    {
        $injector = new Injector($this->module);
        $todoAdd = $injector->getInstance(TodoAddInterface::class);
        assert($todoAdd instanceof TodoAddInterface);
        $todoAdd('uuid1', 'title1');
        $log = (string) $this->logger;
        $this->assertStringContainsString('query:todo_add', $log);
    }

    public function testSelectItem(): void
    {
        $injector = new Injector($this->module);
        $todoItem = $injector->getInstance(TodoItemInterface::class);
        assert($todoItem instanceof TodoItemInterface);
        $item = $todoItem('1');
        $log = (string) $this->logger;
        $this->assertStringContainsString('query:todo_item', $log);
        $this->assertSame(['id' => '1', 'title' => 'run'], $item);
    }
}
