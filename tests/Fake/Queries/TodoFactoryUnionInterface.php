<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Entity\TodoConstructExtended;
use Ray\MediaQuery\Factory\TodoEntityFactory;

interface TodoFactoryUnionInterface
{
    #[DbQuery('todo_item', factory: TodoEntityFactory::class)]
    public function getItem(string $id): TodoConstruct|TodoConstructExtended;

    #[DbQuery('todo_list', factory: TodoEntityFactory::class)]
    /**
     * @return array<TodoConstruct|TodoConstructExtended>
     */
    public function getList(): array;
}
