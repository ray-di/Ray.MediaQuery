<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDOStatement;
use Ray\Di\InjectorInterface;

interface FetchInterface
{
    /** @return array<mixed> */
    public function fetchAll(PDOStatement $pdoStatement, InjectorInterface $injector): array;
}
