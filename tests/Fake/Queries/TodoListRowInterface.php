<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;

interface TodoListRowInterface
{
    /**
     * @DbQuery("todo_list")
     * multiple records but access with type: 'row'
     */
    #[DbQuery('todo_list', type: 'row')]
    public function __invoke(): array;
}