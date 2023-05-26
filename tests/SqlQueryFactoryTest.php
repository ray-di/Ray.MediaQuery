<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

class SqlQueryFactoryTest extends TestCase
{
    public function testGetInstance(): void
    {
        $sqlDir = __DIR__ . '/sql';
        $sqlQuery = SqlQueryFactory::getInstance(
            $sqlDir,
            'sqlite::memory:',
            new Injector(),
        );
        $this->assertInstanceOf(SqlQueryInterface::class, $sqlQuery);
    }
}
