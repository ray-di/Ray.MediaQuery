<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\QueryId;

use function implode;
use function json_encode;
use function sprintf;

use const PHP_EOL;

final class MediaQueryLogger implements MediaQueryLoggerInterface
{
    /** @var list<string> */
    public $logs = [];

    public function log(QueryId $queryId, array $params): void
    {
        $this->logs[] = sprintf('media:%s params:%s', $queryId->id, json_encode($params));
    }

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->logs);
    }
}
