<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Explicit;

use Ray\MediaQuery\Pages;
use Ray\MediaQuery\Queries\TodoListInterface;
use Ray\MediaQuery\SqlQueryInterface;

class TodoList implements TodoListInterface
{
    /** @var SqlQueryInterface */
    private $sqlQuery;

    public function __construct(SqlQueryInterface $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    public function __invoke(): Pages
    {
        return $this->sqlQuery->getPages('todo_list', [], 10);
    }

}
