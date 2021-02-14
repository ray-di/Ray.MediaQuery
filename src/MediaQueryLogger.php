<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use function implode;
use function json_encode;
use function sprintf;

use const PHP_EOL;

final class MediaQueryLogger implements MediaQueryLoggerInterface
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
        $this->logs[] = sprintf('query:%s(%s)', $queryId, json_encode($values));
    }

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->logs);
    }
}
