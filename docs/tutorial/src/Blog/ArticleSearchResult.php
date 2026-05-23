<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class ArticleSearchResult implements PostQueryInterface
{
    /** @param array<Article> $rows */
    public function __construct(
        public readonly array $rows,
        public readonly int $matched,
        public readonly string $sql,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        return new static(
            rows: $context->rows,
            matched: count($context->rows),
            sql: $context->statement->queryString,
        );
    }
}
