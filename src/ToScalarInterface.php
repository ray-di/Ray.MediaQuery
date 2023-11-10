<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface ToScalarInterface
{
    /** @return scalar|array<scalar> */
    public function toScalar(): bool|string|int|float|array;
}
