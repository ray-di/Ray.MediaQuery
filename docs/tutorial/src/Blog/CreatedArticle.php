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
        $article = $context->rows[0] ?? null;
        assert($article instanceof Article);

        return new static($article);
    }
}
