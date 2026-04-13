<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Annotation\Pager;

final class DbPagerTest extends TestCase
{
    public function testPagerInstanceIsNotMutatedAcrossCalls(): void
    {
        $sqlQuery = new class implements SqlQueryInterface {
            /** @var list<int> */
            public array $perPageHistory = [];

            public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array|object|null
            {
                return null;
            }

            public function getRowList(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array
            {
                return [];
            }

            public function exec(string $sqlId, array $values = [], FetchInterface|null $fetch = null): void
            {
            }

            public function getCount(string $sqlId, array $values): int
            {
                return 0;
            }

            public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', string|null $entity = null): PagesInterface
            {
                $this->perPageHistory[] = $perPage;

                return new class implements PagesInterface {
                    /** @var ArrayIterator<int, mixed> */
                    private ArrayIterator $iter;

                    public function __construct()
                    {
                        $this->iter = new ArrayIterator([]);
                    }

                    public function offsetExists(mixed $offset): bool
                    {
                        return $this->iter->offsetExists($offset);
                    }

                    public function offsetGet(mixed $offset): mixed
                    {
                        return $this->iter->offsetGet($offset);
                    }

                    public function offsetSet(mixed $offset, mixed $value): void
                    {
                        $this->iter->offsetSet($offset, $value);
                    }

                    public function offsetUnset(mixed $offset): void
                    {
                        $this->iter->offsetUnset($offset);
                    }

                    public function count(): int
                    {
                        return $this->iter->count();
                    }
                };
            }
        };

        $logger = new class implements MediaQueryLoggerInterface {
            public function start(): void
            {
            }

            public function log(string $queryId, array $values): void
            {
            }

            public function __toString(): string
            {
                return '';
            }
        };

        $dbPager = new DbPager($logger, $sqlQuery);

        // The Pager attribute instance is shared across multiple calls,
        // mirroring how a cached annotation would be reused in production code.
        $pager = new Pager(perPage: 'perPage', template: '/{?page}');

        foreach ([2, 1, 3] as $perPage) {
            ($dbPager)('todo_list', ['perPage' => $perPage], $pager, null);
        }

        $this->assertSame('perPage', $pager->perPage, 'Pager::$perPage must remain the original key string');
        $this->assertSame([2, 1, 3], $sqlQuery->perPageHistory, 'Each call must forward its own perPage to SqlQuery::getPages()');
    }
}
