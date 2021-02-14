<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface MediaQueryLoggerInterface
{
    public function start(): void;

    /**
     * @param array<string, mixed> $values
     */
    public function log(string $queryId, array $values): void;

    public function __toString(): string;
}
