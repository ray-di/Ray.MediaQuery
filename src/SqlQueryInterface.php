<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Result\PostQueryInterface;

/**
 * SQL query executor.
 *
 * Methods in this interface execute the SQL identified by $sqlId every time
 * they are called — the prefix signals the query kind, not a pure accessor:
 *
 *  - `get*`  — SELECT queries; execute and return the result set or a
 *              derivation of it ({@see self::getRow()}, {@see self::getRowList()},
 *              {@see self::getCount()}, {@see self::getPages()}).
 *  - `exec*` — execute and build a typed result. {@see self::exec()} runs a
 *              DML statement without reading a result.
 *              {@see self::execPostQuery()} dispatches through the user-defined
 *              {@see PostQueryInterface} factory for either SELECT (hydrated
 *              rows available on the context) or DML (post-execution PDO state).
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
     * Execute a DML statement without reading a result. Use {@see self::execPostQuery()}
     * when a typed result (row count, last insert id, etc.) is needed.
     *
     * @param array<string, mixed> $values
     *
     * @psalm-taint-escape sql
     */
    public function exec(string $sqlId, array $values = [], FetchInterface|null $fetch = null): void;

    /**
     * Execute a SQL statement and build a result through the given PostQuery class.
     *
     * The framework calls `{$postQueryClass}::fromContext($context)` after
     * executing the SQL. Each result class owns its own construction logic, so
     * the caller's return-type declaration is what selects behaviour (count only,
     * count + last insert id, a typed collection wrapper, etc.). For SELECT
     * statements the rows are fetched and exposed on the context's `$rows`
     * property — shape is determined by the supplied `$fetch` strategy (entity
     * instances for an entity-bound fetch, associative arrays for `FetchAssoc`),
     * or associative arrays when `$fetch` is null. For DML statements no fetch
     * happens and `$rows` is `[]`. When the SQL file contains multiple
     * statements, the result reflects the last executed statement only.
     *
     * @param array<string, mixed> $values
     * @param class-string<T>      $postQueryClass
     * @param FetchInterface|null  $fetch          Strategy used to hydrate SELECT rows. Pass
     *                                             null for DML or to receive associative arrays.
     *
     * @return T
     *
     * @template T of PostQueryInterface
     * @psalm-taint-escape sql
     */
    public function execPostQuery(string $sqlId, array $values, string $postQueryClass, FetchInterface|null $fetch = null): PostQueryInterface;

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
