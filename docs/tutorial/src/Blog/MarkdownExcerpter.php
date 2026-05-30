<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use function mb_strlen;
use function mb_substr;
use function strip_tags;
use function trim;

final class MarkdownExcerpter
{
    public function excerpt(string $body, int $length): string
    {
        $plain = trim(strip_tags($body));
        if (mb_strlen($plain) <= $length) {
            return $plain;
        }

        return mb_substr($plain, 0, $length) . '…';
    }
}
