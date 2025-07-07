<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

// Object with constructor but no Input attributes (for testing hasInputAttribute method)
final class ObjectWithEmptyConstructor
{
    public function __construct(
        public readonly string $value
    ) {}
}