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
        // Keep factory dispatch semantics aligned with FetchFactory.
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
        $injector = $this->injector;
        /** @var object|null $factory */
        $factory = null;

        return static function (array $row) use ($injector, $factoryClass, $factoryMethod, &$factory): mixed {
            $factory ??= $injector->getInstance($factoryClass);

            /** @psalm-suppress MixedMethodCall */
            return $factory->$factoryMethod(...array_values($row));
        };
    }
}
