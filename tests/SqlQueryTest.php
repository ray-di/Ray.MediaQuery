<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use DateTime;
use Pagerfanta\View\DefaultView;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\Di\Injector;
use Ray\InputQuery\ToArray;
use Ray\MediaQuery\Exception\InvalidSqlException;
use Ray\MediaQuery\Exception\LogicException;
use Ray\MediaQuery\Exception\PdoPerformException;
use Ray\MediaQuery\Result\AffectedRows;
use Ray\MediaQuery\Result\InsertedRow;

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
        $pdo->query((string) file_get_contents($sqlDir . '/create_counter.sql'));
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), $this->insertData);
        $this->log = new MediaQueryLogger();
        $this->sqlQuery = new SqlQuery(
            $pdo,
            __DIR__ . '/sql',
            $this->log,
            new AuraSqlPagerFactory(new AuraSqlPager(new DefaultView(), [])),
            new ParamConverter(new ToArray()),
            new Injector(),
            new PerformTemplatedSql('{{ sql }}'),
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

    #[Depends('testExec')]
    public function testGetRow(): void
    {
        $result = $this->sqlQuery->getRow('todo_item', ['id' => '1']);
        $this->assertSame($this->insertData, $result);
    }

    #[Depends('testExec')]
    public function testGetRowNotFound(): void
    {
        $result = $this->sqlQuery->getRow('todo_item', ['id' => '__invalid__']);

        $this->assertNull($result);
    }

    #[Depends('testExec')]
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
        $this->assertSame(2, $page->current);
        $this->assertFalse($page->hasNext);
        $this->assertSame([$walkTodo], $page->data);

        return $pages;
    }

    /** @param Pages<mixed> $pages */
    #[Depends('testPager')]
    public function testPagerCount(Pages $pages): void
    {
        $this->assertSame(2, count($pages));
    }

    /** @param Pages<mixed> $pages */
    #[Depends('testPager')]
    public function testPagerNbPages(Pages $pages): void
    {
        $this->assertSame(2, $pages->getNbPages());
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

    #[Depends('testDateTime')]
    public function testGetStatement(SqlQuery $sqlQuery): void
    {
        $this->assertInstanceOf(PDOStatement::class, $sqlQuery->getStatement());
    }

    /** @param Pages<mixed> $pages */
    #[Depends('testPager')]
    public function testOffsetExists(Pages $pages): void
    {
        $this->assertTrue(isset($pages[1]));
    }

    /** @param Pages<mixed> $pages */
    #[Depends('testPager')]
    public function testOffsetSet(Pages $pages): void
    {
        $this->expectException(LogicException::class);
        $pages[1] = '';
    }

    /** @param Pages<mixed> $pages */
    #[Depends('testPager')]
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

    public function testExecPostQueryForDelete(): void
    {
        $result = $this->sqlQuery->execPostQuery('todo_delete', ['id' => '1'], AffectedRows::class);
        $this->assertInstanceOf(AffectedRows::class, $result);
        $this->assertSame(1, $result->count);
        $this->assertTrue($result->isAffected());
    }

    public function testExecPostQueryForDeleteMissing(): void
    {
        $result = $this->sqlQuery->execPostQuery('todo_delete', ['id' => '__missing__'], AffectedRows::class);
        $this->assertInstanceOf(AffectedRows::class, $result);
        $this->assertSame(0, $result->count);
        $this->assertFalse($result->isAffected());
    }

    public function testExecPostQueryForInsertReturnsResolvedValuesAndId(): void
    {
        $result = $this->sqlQuery->execPostQuery('counter_add', ['label' => 'first'], InsertedRow::class);
        $this->assertInstanceOf(InsertedRow::class, $result);
        $this->assertSame(['label' => 'first'], $result->values);
        $this->assertSame('1', $result->id);
    }
}
