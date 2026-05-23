<?php

declare(strict_types=1);

namespace Tutorial\Blog;

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
