<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;

interface SqlQueryInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function exec(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): void;

    /**
     * @param array<string, mixed> $params
     *
     * @return array<mixed>
     */
    public function getRow(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array;

    /**
     * @param array<string, mixed> $params
     *
     * @return array<array<mixed>>
     */
    public function getRowList(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array;

    /**
     * @param array<string, mixed> $params
     */
    public function getCount(string $sqlId, array $params): int;

    public function getStatement(): ?PDOStatement;

    /**
     * @param array<string, mixed> $params
     */
    public function getPages(string $sqlId, array $params, int $perPage, string $queryTemplate = '/{?page}'): Pages;
}
