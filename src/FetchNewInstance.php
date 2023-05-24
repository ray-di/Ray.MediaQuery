<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;
use Ray\Di\InjectorInterface;

class FetchNewInstance implements FetchInterface
{
    /** @param class-string $entity */
    public function __construct(
        private string $entity,
    ) {
    }

    /** @return array<mixed> */
    public function fetchAll(PDOStatement $pdoStatement, InjectorInterface $injector): array
    {
        $entity = $this->entity;

        /** @psalm-suppress MixedReturnTypeCoercion */
        return $pdoStatement->fetchAll(PDO::FETCH_FUNC, /** @param list<mixed> $args */static function (...$args) use ($entity) {
            /** @psalm-suppress MixedMethodCall */
            return new $entity(...$args);
        });
    }
}
