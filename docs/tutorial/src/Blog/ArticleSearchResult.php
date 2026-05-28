<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use UnexpectedValueException;

/** @template T of Article */
final class ArticleSearchResult implements PostQueryInterface
{
    /** @param list<T> $rows */
    public function __construct(
        public readonly array $rows,
        public readonly int $matched,
        public readonly string $sql,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $matched = count($context->rows);
        $rows = [];
        foreach ($context->rows as $row) {
            if (! $row instanceof Article) {
                throw new UnexpectedValueException('ArticleSearchResult expects Article rows.');
            }

            $rows[] = $row;
        }

        return new static(
            rows: $rows,
            matched: $matched,
            sql: $context->statement->queryString,
        );
    }
}
