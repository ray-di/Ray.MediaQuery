<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

use Aura\Sql\ExtendedPdoInterface;
use PDOStatement;

/**
 * Context passed to {@see PostQueryInterface::fromContext()} after execution.
 *
 * Carries the executed statement, the connection, and the parameter values as
 * resolved by `ParamConverter` / `ParamInjector` — i.e. with injected defaults
 * (UUIDs, timestamps), `DateTime` converted to SQL strings, and `ToScalarInterface`
 * value objects reduced to scalars. These resolved values are what actually went
 * to the driver, and they are not otherwise observable by the caller.
 *
 * For SELECT paths, `$rows` holds the pre-hydrated result set (entity instances
 * when an entity is configured, or associative arrays otherwise). For DML paths
 * no fetch happens and `$rows` is `[]`.
 */
final class PostQueryContext
{
    /**
     * @param array<string, mixed> $values
     * @param array<mixed>         $rows   Hydrated rows for SELECT; `[]` for DML.
     */
    public function __construct(
        public readonly PDOStatement $statement,
        public readonly ExtendedPdoInterface $pdo,
        public readonly array $values,
        public readonly array $rows = [],
    ) {
    }
}
