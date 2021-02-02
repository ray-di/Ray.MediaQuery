<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use Pagerfanta\View\DefaultView;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;

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
        $this->sqlQuery = new SqlQuery($pdo, $this->log, dirname(__DIR__) . '/tests/sql', $pagerFactory);
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

    public function testPager(): void
    {
        $pager = $this->sqlQuery->getPage('todo_list', [], 1);
        $this->assertInstanceOf(AuraSqlPagerInterface::class, $pager);
    }
}
