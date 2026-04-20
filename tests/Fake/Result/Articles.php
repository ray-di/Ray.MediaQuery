<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

use Override;

/**
 * SELECT-path PostQuery result: wraps the hydrated rows as a typed collection.
 *
 * Callers that declare `Articles` as their return type receive the rows via
 * `$context->rows`, pre-hydrated by the framework (entity instances when an
 * entity is configured, associative arrays otherwise).
 */
final class Articles implements PostQueryInterface
{
    /** @param array<mixed> $rows */
    public function __construct(
        public readonly array $rows,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        return new static($context->rows);
    }
}
