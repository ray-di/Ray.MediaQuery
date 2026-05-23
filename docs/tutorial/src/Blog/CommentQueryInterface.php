<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use DateTimeInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\InsertedRow;

interface CommentQueryInterface
{
    #[DbQuery('comment_add')]
    public function add(
        int $articleId,
        string $body,
        ?DateTimeInterface $postedAt = null,
    ): InsertedRow;

    /** @return array<Comment> */
    #[DbQuery('comment_list')]
    public function listFor(int $articleId): array;
}
