<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;

class SqlQuery implements SqlQueryInterface
{
    public function __invoke(string $sqlFile, array $params)
    {
    }
}
