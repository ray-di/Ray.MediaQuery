<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;

interface TodoEntityNullableInterface
{
    #[DbQuery('todo_item')]
    public function getItem(string $id): ?Todo;

    #[DbQuery('todo_list')]
    /**
     * @return array<Todo>
     */
    public function getList(): ?array;
}
