<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use LogicException;
use Ray\MediaQuery\Result\PostQueryInterface;

final class FakeSqlQuery implements SqlQueryInterface
{
    /** @var list<int> */
    public array $perPageHistory = [];

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $values
     */
    public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array|object|null
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $values
     */
    public function getRowList(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $values
     */
    public function exec(string $sqlId, array $values = [], FetchInterface|null $fetch = null): void
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $values
     */
    public function execPostQuery(string $sqlId, array $values, string $postQueryClass): PostQueryInterface
    {
        throw new LogicException('FakeSqlQuery does not support execPostQuery');
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $values
     */
    public function getCount(string $sqlId, array $values): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $values
     */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', string|null $entity = null): PagesInterface
    {
        $this->perPageHistory[] = $perPage;

        return new FakeEmptyPages();
    }
}
