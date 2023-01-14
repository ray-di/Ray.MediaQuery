<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ReflectionMethod;

interface ReturnEntityInterface
{
    /** @return ?class-string  */
    public function __invoke(ReflectionMethod $method): string|null;
}
