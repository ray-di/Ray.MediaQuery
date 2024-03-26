<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;
use Ray\Di\InjectorInterface;

use function assert;
use function class_exists;
use function method_exists;

class FetchInjectionFactory implements FetchInterface
{
    /** @param array{0:string, 1:string} $factory */
    public function __construct(
        private array $factory,
        private string $factoryMethod,
    ) {
    }

    /** @return array<mixed> */
    public function fetchAll(PDOStatement $pdoStatement, InjectorInterface $injector): array
    {
        $factoryClass = $this->factory[0];
        assert(class_exists($factoryClass));
        assert(method_exists($factoryClass, $this->factory[1]));

        $factory = $injector->getInstance($factoryClass);

        return $this->fetchFactory(
            $pdoStatement,
            $factory,
        );
    }

    /** @return array<mixed> */
    private function fetchFactory(PDOStatement $pdoStatement, object $factory): array
    {
        // constructor call

        $method = $this->factoryMethod;

        return $pdoStatement->fetchAll(
            PDO::FETCH_FUNC,
            /**
             * @param list<mixed> $args
             *
             * @retrun mixed
             */
            static function (...$args) use ($factory, $method): mixed {
                assert(method_exists($factory, $method));

                /** @psalm-suppress MixedAssignment */
                return $factory->$method(...$args);
            },
        );
    }
}
