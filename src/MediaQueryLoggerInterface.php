<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\QueryId;

use function implode;

use const PHP_EOL;

interface MediaQueryLoggerInterface
{
    /**
     * @param array<string, string> $params
     */
    public function log(QueryId $queryId, array $params): void;

    public function __toString(): string;
}
