<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeImmutable;

/** @psalm-immutable */
class UnixEpocTime extends DateTimeImmutable
{
    public const TEXT = '1970-01-01 00:00:00';

    public function __construct()
    {
        parent::__construct(self::TEXT);
    }
}
