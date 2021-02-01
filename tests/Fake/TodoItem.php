<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\QueryId;

class TodoItem implements TodoItemInterface
{
    #[QueryId('todo_item')]
    public function __invoke(string $id): array
    {
    }
}
