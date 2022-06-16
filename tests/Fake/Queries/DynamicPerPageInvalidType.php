<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface DynamicPerPageInvalidType
{
    /**
     * @DbQuery("todo_list")
     * @Pager(perPage="perPage", template="/{?page}")
     */
    #[DbQuery('todo_list'), Pager(perPage: 'perPage', template: '/{?page}')]
    public function __invoke($perPage): Pages;
}
