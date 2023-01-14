<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use DateTime;
use Pagerfanta\View\DefaultView;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\Exception\InvalidSqlException;
use Ray\MediaQuery\Exception\LogicException;
use Ray\MediaQuery\Exception\PdoPerformException;

use function assert;
use function count;
use function file_get_contents;

class SqlQueryTest extends TestCase
{
    private SqlQuery $sqlQuery;
    private MediaQueryLogger $log;

    /** @var array<string, mixed> */
    private array $insertData = ['id' => '1', 'title' => 'run'];

    protected function setUp(): void
    {
        $sqlDir = __DIR__ . '/sql';
        $pdo = new ExtendedPdo('sqlite::memory:', '', '', [PDO::ATTR_STRINGIFY_FETCHES => true]);
        $pdo->query((string) file_get_contents($sqlDir . '/create_todo.sql'));
        $pdo->query((string) file_get_contents($sqlDir . '/create_promise.sql'));
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), $this->insertData);
        $this->log = new MediaQueryLogger();
        $this->sqlQuery = new SqlQuery(
            $pdo,
            __DIR__ . '/sql',
            $this->log,
            new AuraSqlPagerFactory(new AuraSqlPager(new DefaultView(), [])),
            new ParamConverter(),
        );
    }

    public function testNewInstance(): void
    {
        $this->assertInstanceOf(SqlQueryInterface::class, $this->sqlQuery);
    }

    public function testExec(): void
    {
        $this->sqlQuery->exec('todo_add', $this->insertData);
        $this->assertStringContainsString('query: todo_add({"id":"1","title":"run"})', (string) $this->log);
    }

    /** @depends testExec */
    public function testGetRow(): void
    {
        $result = $this->sqlQuery->getRow('todo_item', ['id' => '1']);
        $this->assertSame($this->insertData, $result);
    }

    /** @depends testExec */
    public function testGetRowNotFound(): void
    {
        $result = $this->sqlQuery->getRow('todo_item', ['id' => '__invalid__']);

        $this->assertNull($result);
    }

    /** @depends testExec */
    public function testGetRowList(): void
    {
        $result = $this->sqlQuery->getRowList('todo_list', []);
        $this->assertSame([0 => $this->insertData], $result);
    }

    public function testPager(): PagesInterface
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
     * @param Pages<mixed> $pages
     *
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

    public function testDateTime(): SqlQuery
    {
        $dateTime = '2011-10-17 17:47:46';
        $this->sqlQuery->exec('promise_add', ['id' => '1', 'title' => 'talk', 'time' => new DateTime($dateTime)]);
        $item = (array) $this->sqlQuery->getRow('promise_item', ['id' => 1]);
        $this->assertContains($dateTime, $item);

        return $this->sqlQuery;
    }

    public function testInvalidSql(): void
    {
        $this->expectException(InvalidSqlException::class);
        $this->sqlQuery->exec('empty_add', []);
    }

    public function testNotExistsSql(): void
    {
        $this->expectException(InvalidSqlException::class);
        $this->sqlQuery->exec('__not_exists', []);
    }

    /** @depends testDateTime */
    public function testGetStatement(SqlQuery $sqlQuery): void
    {
        $this->assertInstanceOf(PDOStatement::class, $sqlQuery->getStatement());
    }

    /**
     * @param Pages<mixed> $pages
     *
     * @depends testPager
     */
    public function testOffsetExists(Pages $pages): void
    {
        $this->assertTrue(isset($pages[1]));
    }

    /**
     * @param Pages<mixed> $pages
     *
     * @depends testPager
     */
    public function testOffsetSet(Pages $pages): void
    {
        $this->expectException(LogicException::class);
        $pages[1] = '';
    }

    /**
     * @param Pages<mixed> $pages
     *
     * @depends testPager
     */
    public function testOffsetUnset(Pages $pages): void
    {
        $this->expectException(LogicException::class);
        unset($pages[1]);
    }

    public function testWrongSql(): void
    {
        $this->expectException(PdoPerformException::class);
        $this->sqlQuery->getRowList('error_list', []);
    }
}
