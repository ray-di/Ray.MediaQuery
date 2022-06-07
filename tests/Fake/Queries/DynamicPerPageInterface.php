<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface DynamicPerPageInterface
{
    /**
     * @DbQuery("todo_list")
     * @Pager(perPage="num", template="/{?page}")
     */
    #[DbQuery('todo_list'), Pager(perPage: 'num', template: '/{?page}')]
    public function __invoke(int $num): Pages;
}