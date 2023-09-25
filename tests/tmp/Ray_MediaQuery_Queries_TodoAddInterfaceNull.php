<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\AuraSqlModule\Annotation\Transactional;
use Ray\MediaQuery\Annotation\DbQuery;

class TodoAddInterfaceNull implements \Ray\MediaQuery\Queries\TodoAddInterface
{
    
    #[DbQuery('todo_add')]
#[Transactional()]
    public function __invoke(string $id, string $title): void
    {
    }
}