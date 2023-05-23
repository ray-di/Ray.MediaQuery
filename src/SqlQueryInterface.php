<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface SqlQueryInterface
{
    /**
     * @param array<string, mixed> $values
     *
     * @return array<mixed>|object|null
     */
    public function getRow(string $sqlId, array $values = [], Fetch|null $fetch = null): array|object|null;

    /**
     * @param array<string, mixed> $values
     *
     * @return array<array<mixed>>
     */
    public function getRowList(string $sqlId, array $values = [], Fetch|null $fetch = null): array;

    /** @param array<string, mixed> $values */
    public function exec(string $sqlId, array $values = [], Fetch|null $fetch = null): void;

    /** @param array<string, mixed> $values */
    public function getCount(string $sqlId, array $values): int;

    /**
     * @param array<string, mixed> $values
     * @param ?class-string        $entity
     */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', string|null $entity = null): PagesInterface;
}
