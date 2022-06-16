<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

final class DbQueryConfig
{
    /** @var string */
    public $sqlDir;

    public function __construct(string $sqlDir)
    {
        $this->sqlDir = $sqlDir;
    }
}
