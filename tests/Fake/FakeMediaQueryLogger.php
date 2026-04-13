<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

final class FakeMediaQueryLogger implements MediaQueryLoggerInterface
{
    public function start(): void
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $values
     */
    public function log(string $queryId, array $values): void
    {
    }

    public function __toString(): string
    {
        return '';
    }
}
