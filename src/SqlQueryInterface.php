<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Result\AffectedRows;

interface SqlQueryInterface
{
    /**
     * @param array<string, mixed> $values
     *
     * @return array<mixed>|object|null
     *
     * @psalm-taint-escape sql
     */
    public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array|object|null;

    /**
     * @param array<string, mixed> $values
     *
     * @return array<array<mixed>>
     *
     * @psalm-taint-escape sql
     */
    public function getRowList(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array;

    /**
     * @param array<string, mixed> $values
     *
     * @psalm-taint-escape sql
     */
    public function exec(string $sqlId, array $values = [], FetchInterface|null $fetch = null): void;

    /**
     * @param array<string, mixed> $values
     *
     * @psalm-taint-escape sql
     */
    public function getAffectedRows(string $sqlId, array $values = []): AffectedRows;

    /**
     * @param array<string, mixed> $values
     *
     * @psalm-taint-escape sql
     */
    public function getCount(string $sqlId, array $values): int;

    /**
     * @param array<string, mixed> $values
     * @param ?class-string        $entity
     *
     * @psalm-taint-escape sql
     */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', string|null $entity = null): PagesInterface;
}
