<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Entity\TodoConstructExtended;
use Ray\MediaQuery\Factory\TodoEntityFactory;

class TodoFactoryUnionInterfaceNull implements \Ray\MediaQuery\Fake\Queries\TodoFactoryUnionInterface
{
    
    #[DbQuery('todo_item', factory: 'Ray\MediaQuery\Factory\TodoEntityFactory')]
    public function getItem(string $id): TodoConstruct|TodoConstructExtended
    {
    }

    /**
     * @return array<TodoConstruct|TodoConstructExtended>
     */
    #[DbQuery('todo_list', factory: 'Ray\MediaQuery\Factory\TodoEntityFactory')]
    public function getList(): array
    {
    }
}