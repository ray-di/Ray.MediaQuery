<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\DbQuery;
use ReflectionNamedType;
use ReflectionUnionType;

interface FetchFactoryInterface
{
    /** @param class-string|null $entity */
    public function factory(DbQuery $dbQuery, string|null $entity, ReflectionNamedType|ReflectionUnionType|null $returnType): FetchInterface;
}
