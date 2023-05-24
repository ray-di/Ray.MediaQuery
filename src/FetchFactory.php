<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Qualifier\FactoryMethod;
use ReflectionNamedType;
use ReflectionUnionType;

use function class_exists;
use function is_callable;
use function method_exists;

final class FetchFactory implements FetchFactoryInterface
{
    public function __construct(
        #[FactoryMethod] private string $factoryMehtod,
    ) {
    }

    /** {@inheritDoc} */
    public function factory(DbQuery $dbQuery, string|null $entity, ReflectionNamedType|ReflectionUnionType|null $returnType): FetchInterface
    {
        $maybeFactory = [$dbQuery->factory, $this->factoryMehtod];
        if (is_callable($maybeFactory)) {
            // PDO::FETCH_FUNC with static factory method
            return new FetchStaticFactory($maybeFactory);
        }

        if (class_exists($dbQuery->factory) && method_exists($dbQuery->factory, $this->factoryMehtod)) {
            // PDO::FETCH_FUNC with injected factory
            return new FetchInjectionFactory($maybeFactory, $this->factoryMehtod);
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
