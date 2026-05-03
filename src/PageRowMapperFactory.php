<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Di\InjectorInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Qualifier\FactoryMethod;

use function array_values;
use function class_exists;
use function is_callable;
use function method_exists;

final class PageRowMapperFactory
{
    public function __construct(
        #[FactoryMethod]
        private string $factoryMethod,
        private InjectorInterface $injector,
    ) {
    }

    /** @return (callable(array<array-key, mixed>): mixed)|null */
    public function create(DbQuery $dbQuery): callable|null
    {
        $maybeFactory = [$dbQuery->factory, $this->factoryMethod];
        if (is_callable($maybeFactory)) {
            return static function (array $row) use ($maybeFactory): mixed {
                return $maybeFactory(...array_values($row));
            };
        }

        if (! class_exists($dbQuery->factory) || ! method_exists($dbQuery->factory, $this->factoryMethod)) {
            return null;
        }

        $factoryClass = $dbQuery->factory;
        $factoryMethod = $this->factoryMethod;
        $factory = $this->injector->getInstance($factoryClass);

        return static function (array $row) use ($factory, $factoryMethod): mixed {
            /** @psalm-suppress MixedMethodCall */
            return $factory->$factoryMethod(...array_values($row));
        };
    }
}
