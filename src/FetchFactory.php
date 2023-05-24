<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\DbQuery;
use ReflectionNamedType;
use ReflectionUnionType;

use function class_exists;
use function is_callable;
use function method_exists;

final class FetchFactory
{
    /** @param class-string|null $entity */
    public static function factory(DbQuery $dbQuery, string|null $entity, ReflectionNamedType|ReflectionUnionType|null $returnType): FetchInterface
    {
        $maybeFactory = [$dbQuery->factory, 'factory'];
        if (is_callable($maybeFactory)) {
            // PDO::FETCH_FUNC with static factory method
            return new FetchStaticFactory($maybeFactory);
        }

        if (class_exists($dbQuery->factory) && method_exists($dbQuery->factory, 'factory')) {
            // PDO::FETCH_FUNC with injected factory
            return new FetchInjectionFactory($maybeFactory);
        }

        if ($entity === null) {
            // PDO::FETCH_ASSOC
            return new FetchAssoc();
        }

        if (class_exists($entity) && ! method_exists($entity, '__construct')) {
            // PDO::FETCH_CLASS with entity having no constructor
            return new FetchClass($entity);
        }

        // PDO::FETCH_FUNC with entity having constructor
        return new FetchNewInstance($entity);
    }
}
