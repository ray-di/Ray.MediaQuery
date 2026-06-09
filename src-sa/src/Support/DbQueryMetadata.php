<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Support;

final class DbQueryMetadata
{
    public function __construct(
        public readonly string $id,
        public readonly string $factory,
    ) {
    }
}
