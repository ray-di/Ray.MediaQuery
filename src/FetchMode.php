<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;
use Ray\MediaQuery\Annotation\DbQuery;
use ReflectionNamedType;
use ReflectionUnionType;
use function class_exists;
use function is_callable;
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

    public static function factory(DbQuery $dbQuery, string|null $entity, ReflectionNamedType|ReflectionUnionType|null $returnType): FetchMode
    {
        $maybeFactory = [$dbQuery->factory, 'factory'];
        $isFactoryCall = is_callable($maybeFactory) || (class_exists($dbQuery->factory) && method_exists($dbQuery->factory, 'factory'));
        if ($isFactoryCall) {
            return new FetchMode(PDO::FETCH_FUNC, $maybeFactory);
        }

        $mode = match($entity) {
            null => PDO::FETCH_ASSOC,
            default => class_exists($entity) && method_exists($entity, '__construct') ? PDO::FETCH_FUNC : PDO::FETCH_CLASS
        };

        return new FetchMode($mode, (string) $entity);
    }
}
