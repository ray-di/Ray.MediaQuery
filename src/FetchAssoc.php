<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use PDO;
use PDOStatement;
use Ray\Di\InjectorInterface;

final class FetchAssoc implements FetchInterface
{
    public function __construct()
    {
    }

    /** @return array<mixed> */
    #[Override]
    public function fetchAll(PDOStatement $pdoStatement, InjectorInterface $injector): array
    {
        return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }
}
