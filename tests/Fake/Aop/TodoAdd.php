<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Aop;

use Ray\AuraSqlModule\Annotation\Transactional;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\TodoAddInterface;

class TodoAdd implements TodoAddInterface
{
    #[DbQuery, Transactional]
    public function __invoke(string $id, string $title): void
    {
    }
}
