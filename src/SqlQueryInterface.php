<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\Page;

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

    public function getStatement(): ?PDOStatement;

    /**
     * @param array<string, mixed> $params
     */
    public function getPage(string $sqlId, array $params, int $perPage, string $queryTemplate = '/{?page}'): AuraSqlPagerInterface;
}
