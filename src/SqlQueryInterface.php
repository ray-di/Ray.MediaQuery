<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;

interface SqlQueryInterface
{
    /**
     * @param array<string, mixed>                              $values
     * @param PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchMode
     * @param int|string|callable                               $fetchArg
     */
    public function exec(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC, int|string|callable $fetchArg = ''): void;

    /**
     * @param array<string, mixed>                              $values
     * @param PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchMode
     *
     * @return array<mixed>|object|null
     */
    public function getRow(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC, int|string|callable $fetchArg = ''): array|object|null;

    /**
     * @param array<string, mixed>                              $values
     * @param PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchMode
     * @param int|string|callable                               $fetchArg
     *
     * @return array<array<mixed>>
     */
    public function getRowList(string $sqlId, array $values = [], int $fetchMode = PDO::FETCH_ASSOC, int|string|callable $fetchArg = ''): array;

    /** @param array<string, mixed> $values */
    public function getCount(string $sqlId, array $values): int;

    public function getStatement(): PDOStatement|null;

    /**
     * @param array<string, mixed> $values
     * @param ?class-string        $entity
     */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', string|null $entity = null): PagesInterface;
}
