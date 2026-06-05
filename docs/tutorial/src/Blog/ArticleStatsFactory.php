<?php

declare(strict_types=1);

namespace Tutorial\Blog;

final class ArticleStatsFactory
{
    public function __construct(
        private readonly MarkdownExcerpter $excerpter,
    ) {
    }

    public function factory(
        int $id,
        string $title,
        string $body,
        int $commentCount,
        string $status,
    ): ArticleStats {
        return new ArticleStats(
            id: $id,
            title: $title,
            excerpt: $this->excerpter->excerpt($body, 60),
            commentCount: $commentCount,
            published: $status === 'published',
        );
    }
}
