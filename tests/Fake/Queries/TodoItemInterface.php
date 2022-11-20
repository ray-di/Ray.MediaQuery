<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;

interface TodoItemInterface
{
    #[DbQuery('todo_item', type:'row')]
    public function __invoke(string $id): array;
}
