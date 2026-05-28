<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use UnexpectedValueException;

/** @template T of Article */
final class CreatedArticle implements PostQueryInterface
{
    /** @param T $article */
    public function __construct(
        public readonly Article $article,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $article = $context->rows[0] ?? null;
        if (! $article instanceof Article) {
            throw new UnexpectedValueException('CreatedArticle expects the final SELECT to return an Article row.');
        }

        return new static($article);
    }
}
