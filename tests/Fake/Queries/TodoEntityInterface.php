<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;

interface TodoEntityInterface
{
    #[DbQuery('todo_item', entity: Todo::class)]
    public function getItem(string $id): Todo;

    #[DbQuery('todo_list', entity: Todo::class)]
    /**
     * @return array<Todo>
     */
    public function getList(): array;
}
