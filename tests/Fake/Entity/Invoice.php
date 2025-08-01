<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

class Invoice
{
    public function __construct(
        public readonly string $userName,
    ) {}
}