<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\AuraSqlModule\Annotation\Transactional;
use Ray\MediaQuery\Annotation\DbQuery;

interface TodoAddInterface
{
    #[DbQuery('todo_add'), Transactional]
    public function __invoke(string $id, string $title): void;
}
