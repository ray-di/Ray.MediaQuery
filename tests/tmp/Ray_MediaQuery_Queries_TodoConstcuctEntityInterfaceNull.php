<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\TodoConstruct;

class TodoConstcuctEntityInterfaceNull implements \Ray\MediaQuery\Queries\TodoConstcuctEntityInterface
{
    
    #[DbQuery('todo_item')]
    public function getItem(string $id): TodoConstruct
    {
    }

    /**
     * @return array<TodoConstruct>
     */
    #[DbQuery('todo_list')]
    public function getList(): array
    {
    }
}