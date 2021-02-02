<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Aop;

use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\TodoListInterface;

class TodoList implements TodoListInterface
{
    #[DbQuery, Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): AuraSqlPagerInterface
    {
    }
}
