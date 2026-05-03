<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Factory\TodoEntityFactory;
use Ray\MediaQuery\Factory\TodoInjectionFactory;
use Ray\MediaQuery\Pages;

interface PagerFactoryInterface
{
    #[DbQuery('todo_list', factory: TodoEntityFactory::class), Pager(perPage: 10, template: '/{?page}')]
    public function getStatic(): Pages;

    #[DbQuery('todo_list', factory: TodoInjectionFactory::class), Pager(perPage: 10, template: '/{?page}')]
    public function getInjection(): Pages;
}
