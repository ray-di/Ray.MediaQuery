<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\QueryId;

class TodoAdd implements TodoAddInterface
{
    #[QueryId(id: 'todo_add')]
    public function __invoke(string $id, string $title): void
    {
    }
}
