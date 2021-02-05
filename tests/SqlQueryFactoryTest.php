<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use Pagerfanta\View\DefaultView;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactoryInterface;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\Page;

use function assert;
use function count;
use function dirname;

class SqlQueryFactoryTest extends TestCase
{
    public function testGetInstance(): void
    {
        $sqlDir = __DIR__ . '/sql';
        $sqlQuery = SqlQueryFactory::getInstance(
            $sqlDir,
            'sqlite::memory:',
        );
        $this->assertInstanceOf(SqlQueryInterface::class, $sqlQuery);
    }
}
