<?php

declare(strict_types=1);

namespace Tutorial\Blog;

final class Article
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $body,
        public readonly string $authorName,
        public readonly string $status,
        public readonly string|null $publishedAt,
        public readonly string $createdAt,
    ) {
    }
}
