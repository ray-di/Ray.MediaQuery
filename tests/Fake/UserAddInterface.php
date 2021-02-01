<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface UserAddInterface
{
    public function __invoke(string $name, int $age): void;
}
