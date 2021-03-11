<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

final class DbQueryConfig
{
    /** @var Queries */
    public $mediaQueries;

    /** @var string */
    public $sqlDir;

    public function __construct(Queries $mediaQueries, string $sqlDir)
    {
        $this->mediaQueries = $mediaQueries;
        $this->sqlDir = $sqlDir;
    }
}
