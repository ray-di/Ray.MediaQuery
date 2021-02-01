<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use function implode;

use const PHP_EOL;

final class MediaQueryLog
{
    /** @var list<string> */
    public $logs = [];

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->logs);
    }
}
