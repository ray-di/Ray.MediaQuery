<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;

interface SqlQueryInterface
{
    /**
     * @param array<string, mixed> $values
     */
    public function exec(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC): void;

    /**
     * @param array<string, mixed> $values
     *
     * @return array<mixed>
     */
    public function getRow(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC): array;

    /**
     * @param array<string, mixed> $values
     *
     * @return array<array<mixed>>
     */
    public function getRowList(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC): array;

    /**
     * @param array<string, mixed> $values
     */
    public function getCount(string $sqlId, array $values): int;

    public function getStatement(): ?PDOStatement;

    /**
     * @param array<string, mixed> $values
     */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}'): Pages;
}
