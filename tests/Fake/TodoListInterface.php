<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;

interface TodoListInterface
{
    public function __invoke(): AuraSqlPagerInterface;
}
