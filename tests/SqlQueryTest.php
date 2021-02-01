<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use PHPUnit\Framework\TestCase;

class SqlQueryTest extends TestCase
{
    /** @var SqlQuery */
    private $sqlQuery;

    /** @var MediaQueryLog */
    private $log;

    protected function setUp(): void
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $pdo->query(/** @lang sql */'CREATE TABLE IF NOT EXISTS todo (
          id INTEGER,
          title TEXT
)');
        $this->log = new MediaQueryLog();
        $this->sqlQuery = new SqlQuery($pdo, $this->log);
    }

    public function testInvoke(): void
    {
        $result = ($this->sqlQuery)(__DIR__ . '/sql/todo_add.sql', ['id' => 'id1', 'title' => 'titile1']);
        $this->assertSame([], $result);
    }
}
