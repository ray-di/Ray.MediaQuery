<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;
use Ray\Di\InjectorInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use ReflectionNamedType;
use ReflectionUnionType;

use function assert;
use function class_exists;
use function is_array;
use function is_callable;
use function is_string;
use function method_exists;

final class FetchMode
{
    /**
     * @param int                                   $mode
     * @param array{0:string, 1:string}|string|null $args
     */
    public function __construct(
        public int $mode,
        public array|string|null $args,
    ) {
    }

    /** @return array<mixed> */
    public function fetchAll(FetchMode $fetchMode, PDOStatement $pdoStatement, InjectorInterface $injector): array
    {
        if ($this->mode === PDO::FETCH_ASSOC) {
            return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($this->mode === PDO::FETCH_CLASS) {
            assert(is_string($this->args));

            return $pdoStatement->fetchAll(PDO::FETCH_CLASS, $this->args);
        }

        // 'factory' attribute
        if (is_callable($this->args)) {
            return $pdoStatement->fetchAll(PDO::FETCH_FUNC, $this->args);
        }

        if (is_array($this->args)) {
            assert(class_exists($this->args[0]));
            assert(method_exists($this->args[0], $this->args[1]));

            return $this->fetchFactory(
                $pdoStatement,
                $injector->getInstance($this->args[0]),
            );
        }

        assert(is_string($this->args));
        assert(class_exists($this->args));

        return $this->fetchNewInstance($pdoStatement, $this->args);
    }

    /**
     * @param class-string<T> $entity
     *
     * @return array<T>
     *
     * @template T
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function fetchNewInstance(PDOStatement $pdoStatement, string $entity): array
    {
        /** @psalm-suppress MixedReturnTypeCoercion */
        return $pdoStatement->fetchAll(PDO::FETCH_FUNC, /** @param list<mixed> $args */static function (...$args) use ($entity) {
            /** @psalm-suppress MixedMethodCall */
            return new $entity(...$args);
        });
    }

    /** @return array<mixed> */
    private function fetchFactory(PDOStatement $pdoStatement, object $factory): array
    {
        // constructor call

        return $pdoStatement->fetchAll(
            PDO::FETCH_FUNC,
            /**
             * @param list<mixed> $args
             *
             * @retrun mixed
             */
            static function (...$args) use ($factory): mixed {
                assert(method_exists($factory, 'factory'));

                /** @psalm-suppress MixedAssignment */
                return $factory->factory(...$args);
            },
        );
    }

    public static function factory(DbQuery $dbQuery, string|null $entity, ReflectionNamedType|ReflectionUnionType|null $returnType): FetchMode
    {
        $maybeFactory = [$dbQuery->factory, 'factory'];
        $isFactoryCall = is_callable($maybeFactory) || (class_exists($dbQuery->factory) && method_exists($dbQuery->factory, 'factory'));
        if ($isFactoryCall) {
            return new FetchMode(PDO::FETCH_FUNC, $maybeFactory);
        }

        $mode = match ($entity) {
            null => PDO::FETCH_ASSOC,
            default => class_exists($entity) && method_exists($entity, '__construct') ? PDO::FETCH_FUNC : PDO::FETCH_CLASS
        };

        return new FetchMode($mode, (string) $entity);
    }
}
