<?php

declare(strict_types=1);

interface UserAddInterface
{
    public function __invoke(string $id, string $name): void;
}
