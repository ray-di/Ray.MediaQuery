<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\TodoConstruct;

interface TodoConstcuctEntityInterface
{
    #[DbQuery('todo_item')]
    public function getItem(string $id): TodoConstruct;

    #[DbQuery('todo_list')]
    /**
     * @return array<TodoConstruct>
     */
    public function getList(): array;
}
