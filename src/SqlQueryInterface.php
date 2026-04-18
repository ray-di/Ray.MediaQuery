<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Result\AffectedRows;

/**
 * SQL query executor.
 *
 * Methods in this interface execute the SQL identified by $sqlId every time
 * they are called — the prefix signals the query kind, not a pure accessor:
 *
 *  - `get*`  — SELECT queries; execute and return the result set or a
 *              derivation of it ({@see self::getRow()}, {@see self::getRowList()},
 *              {@see self::getCount()}, {@see self::getPages()}).
 *  - `exec*` — DML queries (INSERT / UPDATE / DELETE); execute and either
 *              return nothing ({@see self::exec()}) or return the affected
 *              row count and last insert id ({@see self::execAffected()}).
 */
interface SqlQueryInterface
{
    /**
     * Execute a SELECT and return the first row, or null when no rows match.
     *
     * @param array<string, mixed> $values
     *
     * @return array<mixed>|object|null
     *
     * @psalm-taint-escape sql
     */
    public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array|object|null;

    /**
     * Execute a SELECT and return all rows.
     *
     * @param array<string, mixed> $values
     *
     * @return array<array<mixed>>
     *
     * @psalm-taint-escape sql
     */
    public function getRowList(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array;

    /**
     * Execute a DML statement without reading a result. Use {@see self::execAffected()}
     * when the affected row count or last insert id is needed.
     *
     * @param array<string, mixed> $values
     *
     * @psalm-taint-escape sql
     */
    public function exec(string $sqlId, array $values = [], FetchInterface|null $fetch = null): void;

    /**
     * Execute a DML statement and return the affected row count and, for INSERT,
     * the last insert id. When the SQL file contains multiple statements, the
     * result reflects the last executed statement only.
     *
     * @param array<string, mixed> $values
     *
     * @psalm-taint-escape sql
     */
    public function execAffected(string $sqlId, array $values = []): AffectedRows;

    /**
     * Return the total row count for a SELECT. Used as the pagination denominator.
     *
     * @param array<string, mixed> $values
     *
     * @psalm-taint-escape sql
     */
    public function getCount(string $sqlId, array $values): int;

    /**
     * Execute a SELECT through the paginator and return a lazy Pages wrapper.
     *
     * @param array<string, mixed> $values
     * @param ?class-string        $entity
     *
     * @psalm-taint-escape sql
     */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', string|null $entity = null): PagesInterface;
}
