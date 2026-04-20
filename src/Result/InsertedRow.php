<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

use Override;

/**
 * Data inserted by an INSERT statement — the resolved parameter values bound to
 * the driver, plus the auto-increment id the driver reports back.
 *
 * Declare `InsertedRow` as the return type of a `#[DbQuery]` method to recover
 * values that the framework injected on the caller's behalf (UUIDs for null
 * defaults, timestamps, `DateTime` → SQL-string conversion, `ToScalarInterface`
 * value objects reduced to scalars). Those resolved values are what actually
 * went to the database and are not otherwise observable by the caller.
 *
 * For bulk `INSERT ... SELECT` or `ON DUPLICATE KEY UPDATE` where only the row
 * count matters, use {@see AffectedRows} instead.
 */
final class InsertedRow implements PostQueryInterface
{
    /**
     * @param array<string, mixed> $values Parameter values resolved by ParamConverter /
     *                                     ParamInjector and bound to the driver.
     * @param string|null          $id     Auto-increment id assigned by the INSERT, or null
     *                                     when the driver reports none (tables without
     *                                     AUTO_INCREMENT, or values `false` / `''` / `'0'`,
     *                                     all normalised to null).
     */
    public function __construct(
        public readonly array $values,
        public readonly string|null $id,
    ) {
    }

    #[Override]
    public static function postQuery(PostQueryContext $context): static
    {
        $id = $context->pdo->lastInsertId();
        $lastInsertId = $id === false || $id === '' || $id === '0' ? null : $id;

        return new static($context->values, $lastInsertId);
    }
}
