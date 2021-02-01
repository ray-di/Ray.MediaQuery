<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface TodoAddInterface
{
    public function __invoke(string $id, string $title): void;
}
