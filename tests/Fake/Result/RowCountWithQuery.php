<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

use Override;

final class RowCountWithQuery implements PostQueryInterface
{
    public function __construct(
        public readonly int $count,
        public readonly string $queryString,
    ) {
    }

    #[Override]
    public static function postQuery(PostQueryContext $context): static
    {
        return new static($context->statement->rowCount(), $context->statement->queryString);
    }
}
