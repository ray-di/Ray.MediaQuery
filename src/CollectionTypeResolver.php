<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ReflectionClass;
use Traversable;

use function class_exists;

final class CollectionTypeResolver
{
    public static function isCollection(string $type): bool
    {
        if (! class_exists($type)) {
            return false;
        }

        $rc = new ReflectionClass($type);
        if (! $rc->isInstantiable() || ! $rc->implementsInterface(Traversable::class)) {
            return false;
        }

        $ctor = $rc->getConstructor();

        return $ctor !== null && $ctor->getNumberOfParameters() >= 1;
    }
}
