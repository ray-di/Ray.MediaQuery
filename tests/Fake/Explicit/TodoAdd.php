<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Explicit;

use Ray\MediaQuery\Queries\TodoAddInterface;
use Ray\MediaQuery\SqlQueryInterface;

class TodoAdd implements TodoAddInterface
{
    public function __construct(
        private SqlQueryInterface $sqlQuery
    ){
    }

    public function __invoke(string $id, string $title): void
    {
        $this->sqlQuery->exec('todo_add', ['id' => $id, 'title' => $title]);
    }
}
