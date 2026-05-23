<?php

declare(strict_types=1);

namespace Tutorial\Blog;

final class Comment
{
    public function __construct(
        public readonly int $id,
        public readonly int $articleId,
        public readonly string $body,
        public readonly string $postedAt,
    ) {
    }
}
