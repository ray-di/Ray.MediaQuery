<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Explicit;

use Ray\MediaQuery\Queries\TodoItemInterface;
use Ray\MediaQuery\SqlQueryInterface;

class TodoItem implements TodoItemInterface
{
    /** @var SqlQueryInterface */
    private $sqlQuery;

    public function __construct(SqlQueryInterface $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    public function __invoke(string $id): array
    {
        return $this->sqlQuery->getRow('todo_item', ['id' => $id]);
    }
}
