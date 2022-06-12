<?php

declare(strict_types=1);

namespace Ray\MediaQuery\DbQuery;

use Ray\MediaQuery\ClassesInDirectories;

final class Queries
{
    /**
     * @var list<class-string>
     * @readonly
     */
    public $classes;

    /**
     * @param list<class-string> $mediaQueryClasses
     */
    private function __construct(array $mediaQueryClasses)
    {
        $this->classes = $mediaQueryClasses;
    }

    /**
     * @param list<class-string> $mediaQueryClasses
     */
    public static function fromClasses(array $mediaQueryClasses): self
    {
        return new self($mediaQueryClasses);
    }

    public static function fromDir(string $queryDir): self
    {
        $classes = [];
        $genClasses = ClassesInDirectories::list($queryDir);
        foreach ($genClasses as $class) {
            $classes[] = $class;
        }

        return new self($classes);
    }
}
