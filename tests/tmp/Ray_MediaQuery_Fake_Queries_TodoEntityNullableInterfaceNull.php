<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;

class TodoEntityNullableInterfaceNull implements \Ray\MediaQuery\Fake\Queries\TodoEntityNullableInterface
{
    
    #[DbQuery('todo_item')]
    public function getItem(string $id): ?Todo
    {
    }

    /**
     * @return array<Todo>
     */
    #[DbQuery('todo_list')]
    public function getList(): ?array
    {
    }
}