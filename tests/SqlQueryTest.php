<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use Pagerfanta\View\DefaultView;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;
use Ray\AuraSqlModule\Pagerfanta\Page;
use function assert;
use function count;
use function dirname;

class SqlQueryTest extends TestCase
{
    /** @var SqlQuery */
    private $sqlQuery;

    /** @var MediaQueryLogger */
    private $log;

    /** @var array<string, mixed> */
    private $insertData = ['id' => '1', 'title' => 'run'];

    protected function setUp(): void
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $pdo->query(/** @lang sql */'CREATE TABLE IF NOT EXISTS todo (
          id INTEGER,
          title TEXT
)');
        $pdo->perform(/** @lang sql */'INSERT INTO todo (id, title) VALUES (:id, :title)', $this->insertData);
        $this->log = new MediaQueryLogger();
        $pagerFactory = new AuraSqlPagerFactory(new AuraSqlPager(new DefaultView(), []));
        $this->sqlQuery = new SqlQuery($pdo, dirname(__DIR__) . '/tests/sql', $this->log, $pagerFactory);
    }

    public function testNewInstance(): void
    {
        $sqlDir = __DIR__ . '/sql';
        $sqlQuery = new SqlQuery(
            new ExtendedPdo('sqlite::memory:'),
            $sqlDir,
            new MediaQueryLogger(),
            new AuraSqlPagerFactory(new AuraSqlPager(new DefaultView(), []))
        );
        $this->assertInstanceOf(SqlQueryInterface::class, $sqlQuery);
    }

    public function testExec(): void
    {
        $this->sqlQuery->exec('todo_add', $this->insertData);
        $this->assertStringContainsString('query:todo_add({"id":"1","title":"run"})', (string) $this->log);
    }

    /**
     * @depends testExec
     */
    public function testGetRow(): void
    {
        $result = $this->sqlQuery->getRow('todo_item', ['id' => '1']);
        $this->assertSame($this->insertData, $result);
    }

    /**
     * @depends testExec
     */
    public function testGetRowList(): void
    {
        $result = $this->sqlQuery->getRowList('todo_list', []);
        $this->assertSame([0 => $this->insertData], $result);
    }

    public function testPager(): Pages
    {
        $walkTodo = ['id' => '2', 'title' => 'walk'];
        $this->sqlQuery->exec('todo_add', $walkTodo);
        $pages = $this->sqlQuery->getPages('todo_list', [], 1);
        $this->assertInstanceOf(Pages::class, $pages);
        $page = $pages[2];
        $this->assertInstanceOf(Page::class, $page);
        assert($page instanceof Page);
        $this->assertSame(2, $page->current);
        $this->assertFalse($page->hasNext);
        $this->assertSame([$walkTodo], $page->data);

        return $pages;
    }

    /**
     * @depends testPager
     */
    public function testPagerCount(Pages $pages): void
    {
        $this->assertSame(2, count($pages));
    }

    public function testCount(): void
    {
        $this->sqlQuery->exec('todo_add', ['id' => '2', 'title' => 'walk']);
        $count = $this->sqlQuery->getCount('todo_list', []);
        $this->assertSame(2, $count);
    }
}
