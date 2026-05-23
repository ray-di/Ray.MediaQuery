<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class CreatedArticle implements PostQueryInterface
{
    public function __construct(
        public readonly Article $article,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        /** @var list<Article> $rows */
        $rows = $context->rows;

        return new static($rows[0]);
    }
}
