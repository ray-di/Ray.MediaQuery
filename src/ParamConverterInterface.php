<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface ParamConverterInterface
{
    /** @param array<string, mixed> $values */
    public function __invoke(array &$values): void;
}
