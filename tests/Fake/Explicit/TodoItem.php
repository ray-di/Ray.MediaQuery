<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Explicit;

use Ray\MediaQuery\Queries\TodoItemInterface;
use Ray\MediaQuery\SqlQueryInterface;

class TodoItem implements TodoItemInterface
{
    public function __construct(
        private SqlQueryInterface $sqlQuery
    ){
    }

    public function __invoke(string $id): array
    {
        return $this->sqlQuery->getRow('todo_item', ['id' => $id]);
    }
}
