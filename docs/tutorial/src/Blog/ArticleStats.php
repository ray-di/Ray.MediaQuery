<?php

declare(strict_types=1);

namespace Tutorial\Blog;

final class ArticleStats
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $excerpt,
        public readonly int $commentCount,
        public readonly bool $published,
    ) {
    }
}
