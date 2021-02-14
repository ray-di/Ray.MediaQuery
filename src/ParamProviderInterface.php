<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ReflectionParameter;

interface ParamProviderInterface
{
    /**
     * @param array<ReflectionParameter> $parameters
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $parameters): array;
}
