<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Stringable;

use function base64_encode;
use function implode;
use function is_string;
use function json_encode;
use function mb_check_encoding;
use function sprintf;

use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

final class MediaQueryLogger implements MediaQueryLoggerInterface, Stringable
{
    /** @var list<string> */
    public $logs = [];

    public function start(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function log(string $queryId, array $values): void
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($values as &$value) {
            if (! is_string($value) || mb_check_encoding($value, 'UTF-8')) {
                continue;
            }

            $value = base64_encode($value); // or '(binary) ' . base64_encode($value); // or '(binary) ' .
        }

        $this->logs[] = sprintf('query: %s(%s)', $queryId, json_encode($values, JSON_THROW_ON_ERROR));
    }

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->logs);
    }
}
