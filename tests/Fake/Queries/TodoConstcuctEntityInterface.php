<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\TodoConstruct;

interface TodoConstcuctEntityInterface
{
    #[DbQuery('todo_item', entity: TodoConstruct::class, type: "row")]
    public function getItem(string $id): TodoConstruct;

    #[DbQuery('todo_list', entity: TodoConstruct::class)]
    public function getList(): array;
}
