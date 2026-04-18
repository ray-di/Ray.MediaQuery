<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

final class AffectedRows
{
    public function __construct(
        public readonly int $count,
        public readonly string|null $lastInsertId = null,
    ) {
    }

    public function isAffected(): bool
    {
        return $this->count > 0;
    }
}
