<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;

interface TodoConstcuctEntityInterface
{
    #[DbQuery('todo_item', entity: TodoConstruct::class)]
    public function getItem(string $id): TodoConstruct;

    /** @return list<Todo> */
    #[DbQuery('todo_list', entity: TodoConstruct::class)]
    public function getlist(): array;
}
