<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;

interface TodoEntityInterface
{
    #[DbQuery('todo_item')]
    public function getItem(string $id): Todo;

    #[DbQuery('todo_list')]
    /**
     * @return array<Todo>
     */
    public function getList(): array;

    #[DbQuery('todo_list_join')]
    public function getListWithMemo(string $id): Todo;
}
