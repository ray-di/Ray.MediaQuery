<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Explicit;

use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\MediaQuery\SqlQueryInterface;
use Ray\MediaQuery\TodoListInterface;

class TodoList implements TodoListInterface
{
    /** @var SqlQueryInterface */
    private $sqlQuery;

    public function __construct(SqlQueryInterface $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    public function __invoke(): AuraSqlPagerInterface
    {
        return $this->sqlQuery->getPages('todo_list', [], 10);
    }
}
