<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use DateTimeInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;
use Ray\MediaQuery\Result\AffectedRows;
use Ray\MediaQuery\Result\InsertedRow;

interface ArticleQueryInterface
{
    /** @return array<Article> */
    #[DbQuery('article_list')]
    public function list(): array;

    #[DbQuery('article_item', type: 'row')]
    public function item(ArticleId $id): ?Article;

    #[DbQuery('article_add')]
    public function add(
        string $title,
        string $body,
        string $authorName,
        string $status = 'draft',
        ?DateTimeInterface $publishedAt = null,
        ?DateTimeInterface $createdAt = null,
    ): InsertedRow;

    #[DbQuery('article_update')]
    public function update(ArticleId $id, string $title, string $body): AffectedRows;

    #[DbQuery('article_delete')]
    public function delete(ArticleId $id): AffectedRows;

    /** @return Pages<Article> */
    #[DbQuery('article_paginated'), Pager(perPage: 10)]
    public function paginated(): Pages;

    /** @return Pages<ArticleStats> */
    #[DbQuery('article_stats_paginated', factory: ArticleStatsFactory::class), Pager(perPage: 10)]
    public function statsPaginated(): Pages;

    #[DbQuery('article_stats', type: 'row', factory: ArticleStatsFactory::class)]
    public function stats(ArticleId $id): ArticleStats;

    /** @return ArticleSearchResult<Article> */
    #[DbQuery('article_search')]
    public function search(string $keyword): ArticleSearchResult;

    /** @return CreatedArticle<Article> */
    #[DbQuery('article_create_and_get')]
    public function createAndGet(
        string $title,
        string $body,
        string $authorName,
        string $status = 'draft',
        ?DateTimeInterface $createdAt = null,
    ): CreatedArticle;
}
