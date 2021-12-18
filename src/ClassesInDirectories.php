<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Generator;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;

use const PHP_VERSION_ID;

final class ClassesInDirectories
{
    /**
     * get a list of all classes in the given directories.
     *
     * Based on: https://github.com/Roave/BetterReflection/blob/396a07c9d276cb9ffba581b24b2dadbb542d542e/demo/parsing-whole-directory/example2.php.
     *
     * @param list<string> $directories
     *
     * @return Generator<int, class-string>
     *
     * This function code is taken from https://github.com/WyriHaximus/php-list-classes-in-directory/blob/master/src/functions.php
     * and modified for roave/better-reflection 5.x
     *
     * @see https://github.com/WyriHaximus/php-list-classes-in-directory
     * @psalm-suppress MixedReturnTypeCoercion
     * @phpstan-ignore-next-line/
     */
    public static function list(string ...$directories): iterable
    {
        /** @var list<string> $directories */
        $sourceLocator = new AggregateSourceLocator([
            new DirectoriesSourceLocator(
                $directories,
                (new BetterReflection())->astLocator()
            ),
            // ↓ required to autoload parent classes/interface from another directory than /src (e.g. /vendor)
            new AutoloadSourceLocator((new BetterReflection())->astLocator()),
        ]);

        if (PHP_VERSION_ID >= 80000) {
            foreach ((new DefaultReflector($sourceLocator))->reflectAllClasses() as $class) {
                yield $class->getName(); // @phpstan-ignore-line
            }

            return;
        }

        /** @psalm-suppress all */
        foreach ((new ClassReflector($sourceLocator))->getAllClasses() as $class) { // @phpstan-ignore-line
            yield $class->getName();
        }
    }
}
