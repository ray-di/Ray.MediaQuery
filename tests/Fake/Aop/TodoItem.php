<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Aop;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\TodoItemInterface;

class TodoItem implements TodoItemInterface
{
    #[DbQuery]
    public function __invoke(string $id): array
    {
    }
}
