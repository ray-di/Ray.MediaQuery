<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Factory\TodoEntityFactory;

interface TodoFactoryInterface
{
    #[DbQuery('todo_item', factory: TodoEntityFactory::class)]
    public function getItem(string $id): TodoConstruct;

    #[DbQuery('todo_list', factory: TodoEntityFactory::class)]
    /**
     * @return array<TodoConstruct>
     */
    public function getList(): array;
}
