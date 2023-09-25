<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Factory\TodoEntityFactory;
use Ray\MediaQuery\Factory\TodoInjectionFactory;

class TodoFactoryInterfaceNull implements \Ray\MediaQuery\Fake\Queries\TodoFactoryInterface
{
    
    #[DbQuery('todo_item', factory: 'Ray\MediaQuery\Factory\TodoEntityFactory')]
    public function getItem(string $id): TodoConstruct
    {
    }

    /**
     * @return array<TodoConstruct>
     */
    #[DbQuery('todo_list', factory: 'Ray\MediaQuery\Factory\TodoEntityFactory')]
    public function getList(): array
    {
    }

    /**
     * @return array<TodoConstruct>
     */
    #[DbQuery('todo_list', factory: 'Ray\MediaQuery\Factory\TodoInjectionFactory')]
    public function getListInjection(): array
    {
    }
}