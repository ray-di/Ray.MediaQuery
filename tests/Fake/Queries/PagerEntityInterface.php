<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Pages;

interface PagerEntityInterface
{
    #[DbQuery('todo_list', entity: TodoConstruct::class), Pager(perPage: 10, template: '/{?page}')]
    public function __invoke(): Pages;
}
