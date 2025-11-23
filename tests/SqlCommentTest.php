<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use Pagerfanta\View\DefaultView;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;
use Ray\Di\Injector;
use Ray\InputQuery\ToArray;

use function file_get_contents;

class SqlCommentTest extends TestCase
{
    private SqlQuery $sqlQuery;

    /** @var array<string, mixed> */
    private array $insertData = ['id' => '1', 'title' => 'test'];

    protected function setUp(): void
    {
        $sqlDir = __DIR__ . '/sql';
        $pdo = new ExtendedPdo('sqlite::memory:', '', '', [PDO::ATTR_STRINGIFY_FETCHES => true]);
        $pdo->query((string) file_get_contents($sqlDir . '/create_todo.sql'));
        $pdo->perform((string) file_get_contents($sqlDir . '/todo_add.sql'), $this->insertData);

        $this->sqlQuery = new SqlQuery(
            $pdo,
            __DIR__ . '/sql',
            new MediaQueryLogger(),
            new AuraSqlPagerFactory(new AuraSqlPager(new DefaultView(), [])),
            new ParamConverter(new ToArray()),
            new Injector(),
            new PerformTemplatedSql('{{ sql }}'),
        );
    }

    public function testInsertWithSelectInComment(): void
    {
        // Critical test: -- SELECT comment should not cause INSERT to be detected as SELECT
        $data = ['id' => '2', 'title' => 'test2'];
        $this->sqlQuery->exec('todo_insert_with_select_comment', $data);

        // Verify the INSERT worked
        $result = $this->sqlQuery->getRow('todo_item', ['id' => '2']);
        $this->assertSame($data, $result);
    }

    public function testSelectWithLeadingDashComment(): void
    {
        $result = $this->sqlQuery->getRow('todo_select_with_leading_comment', ['id' => '1']);
        $this->assertSame($this->insertData, $result);
    }

    public function testUpdateWithInsertComment(): void
    {
        $updatedTitle = 'updated';
        $this->sqlQuery->exec('todo_update_with_insert_comment', ['id' => '1', 'title' => $updatedTitle]);

        // Verify the UPDATE worked
        $result = $this->sqlQuery->getRow('todo_item', ['id' => '1']);
        $this->assertSame(['id' => '1', 'title' => $updatedTitle], $result);
    }

    public function testDashesInStringLiteral(): void
    {
        // Test that -- inside string literals is not stripped
        // This query should work correctly despite having -- in the WHERE clause
        $result = $this->sqlQuery->getRow('todo_with_dashes_in_string', ['id' => '1']);
        // Since 'test--value' won't match 'test', we expect null
        $this->assertNull($result);
    }
}
