<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface ToScalarInterface
{
    public function toScalar(): bool|string|int|float;
}
