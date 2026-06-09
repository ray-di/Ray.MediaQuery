<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Support;

use function ctype_alnum;
use function ctype_alpha;
use function strlen;

final class NamedParameterExtractor
{
    private const DEFAULT = 'default';
    private const SINGLE_QUOTE = 'single_quote';
    private const DOUBLE_QUOTE = 'double_quote';
    private const BACKTICK = 'backtick';
    private const BRACKET = 'bracket';
    private const LINE_COMMENT = 'line_comment';
    private const BLOCK_COMMENT = 'block_comment';

    /** @return list<string> */
    public function extract(string $sql): array
    {
        $state = self::DEFAULT;
        $parameters = [];
        $seen = [];
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($state === self::LINE_COMMENT) {
                if ($char === "\n" || $char === "\r") {
                    $state = self::DEFAULT;
                }

                continue;
            }

            if ($state === self::BLOCK_COMMENT) {
                if ($char === '*' && $next === '/') {
                    $i++;
                    $state = self::DEFAULT;
                }

                continue;
            }

            if ($state === self::SINGLE_QUOTE) {
                if ($char === "'" && $next === "'") {
                    $i++;
                    continue;
                }

                if ($char === "'") {
                    $state = self::DEFAULT;
                }

                continue;
            }

            if ($state === self::DOUBLE_QUOTE) {
                if ($char === '"' && $next === '"') {
                    $i++;
                    continue;
                }

                if ($char === '"') {
                    $state = self::DEFAULT;
                }

                continue;
            }

            if ($state === self::BACKTICK) {
                if ($char === '`') {
                    $state = self::DEFAULT;
                }

                continue;
            }

            if ($state === self::BRACKET) {
                if ($char === ']') {
                    $state = self::DEFAULT;
                }

                continue;
            }

            if ($char === '-' && $next === '-') {
                $i++;
                $state = self::LINE_COMMENT;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $i++;
                $state = self::BLOCK_COMMENT;
                continue;
            }

            if ($char === "'") {
                $state = self::SINGLE_QUOTE;
                continue;
            }

            if ($char === '"') {
                $state = self::DOUBLE_QUOTE;
                continue;
            }

            if ($char === '`') {
                $state = self::BACKTICK;
                continue;
            }

            if ($char === '[') {
                $state = self::BRACKET;
                continue;
            }

            if ($char !== ':') {
                continue;
            }

            $previous = $sql[$i - 1] ?? '';
            if ($previous === ':' || $next === ':' || $next === '=') {
                continue;
            }

            if (! $this->isNameStart($next)) {
                continue;
            }

            $name = $next;
            $i++;
            while ($i + 1 < $length && $this->isNameChar($sql[$i + 1])) {
                $i++;
                $name .= $sql[$i];
            }

            if (isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $parameters[] = $name;
        }

        return $parameters;
    }

    private function isNameStart(string $char): bool
    {
        return $char === '_' || ($char !== '' && ctype_alpha($char));
    }

    private function isNameChar(string $char): bool
    {
        return $char === '_' || ($char !== '' && ctype_alnum($char));
    }
}
