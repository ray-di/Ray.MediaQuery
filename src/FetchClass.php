<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use PDO;
use PDOStatement;
use Ray\Di\InjectorInterface;

final class FetchClass implements FetchInterface
{
    /** @param class-string $entity */
    public function __construct(
        private string $entity,
    ) {
    }

    /** @return array<mixed> */
    #[Override]
    public function fetchAll(PDOStatement $pdoStatement, InjectorInterface $injector): array
    {
        return $pdoStatement->fetchAll(PDO::FETCH_CLASS, $this->entity);
    }
}
