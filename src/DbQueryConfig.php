<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

final class DbQueryConfig
{
    public function __construct(
        public string $sqlDir,
    ) {
    }
}
