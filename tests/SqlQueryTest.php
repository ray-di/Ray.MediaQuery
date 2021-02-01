<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use PHPUnit\Framework\TestCase;

class SqlQueryTest extends TestCase
{
    /** @var SqlQuery */
    private $sqlQuery;

    protected function setUp(): void
    {
        $this->sqlQuery = new SqlQuery();
    }

    public function testInvoke(): void
    {
        ($this->sqlQuery)(__DIR__ . '/sql/todo_add.sql', []);
        $this->assertStringContainsString('request:user_add', (string) $log);
    }
}
