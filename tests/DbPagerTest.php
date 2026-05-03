<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Annotation\Pager;

final class DbPagerTest extends TestCase
{
    public function testPagerInstanceIsNotMutatedAcrossCalls(): void
    {
        $sqlQuery = new FakeSqlQuery();
        $dbPager = new DbPager(new FakeMediaQueryLogger(), $sqlQuery);

        // The Pager attribute instance is shared across multiple calls,
        // mirroring how a cached annotation would be reused in production code.
        $pager = new Pager(perPage: 'perPage', template: '/{?page}');

        foreach ([2, 1, 3] as $perPage) {
            ($dbPager)('todo_list', ['perPage' => $perPage], $pager, null);
        }

        $this->assertSame('perPage', $pager->perPage, 'Pager::$perPage must remain the original key string');
        $this->assertSame([2, 1, 3], $sqlQuery->perPageHistory, 'Each call must forward its own perPage to SqlQuery::getPages()');
    }

    public function testNonPagesResultIsWrappedWhenRowMapperIsGiven(): void
    {
        $dbPager = new DbPager(new FakeMediaQueryLogger(), new FakeSqlQuery());
        $pager = new Pager(perPage: 10, template: '/{?page}');

        $pages = ($dbPager)('todo_list', [], $pager, null, static fn (array $row): array => $row);

        $this->assertInstanceOf(MappedPages::class, $pages);
    }
}
