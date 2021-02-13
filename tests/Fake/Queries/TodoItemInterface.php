<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;

interface TodoItemInterface
{
    /**
     * @return array{id: string, title: string}
     */
    #[DbQuery('todo_item')]
    public function __invoke(string $id): array;
}
