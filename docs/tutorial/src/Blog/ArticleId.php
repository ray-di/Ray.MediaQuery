<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Ray\MediaQuery\ToScalarInterface;

final class ArticleId implements ToScalarInterface
{
    public function __construct(
        public readonly int $value,
    ) {
    }

    public function toScalar(): int
    {
        return $this->value;
    }
}
