<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

use Override;

/**
 * Row-count result for UPDATE / DELETE statements.
 *
 * Declare `AffectedRows` as the return type of a `#[DbQuery]` method to receive
 * the number of rows affected by the statement. Use {@see InsertedRow} instead
 * when the caller needs the auto-increment id and the resolved values after an
 * INSERT.
 */
final class AffectedRows implements PostQueryInterface
{
    /** @param int $count Number of rows affected by the last executed statement. */
    public function __construct(
        public readonly int $count,
    ) {
    }

    #[Override]
    public static function postQuery(PostQueryContext $context): static
    {
        return new static($context->statement->rowCount());
    }

    public function isAffected(): bool
    {
        return $this->count > 0;
    }
}
