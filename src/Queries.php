<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileInfo;

use function assert;
use function interface_exists;
use function is_dir;
use function sort;

final class Queries
{
    /**
     * @var list<class-string>
     * @psalm-readonly
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
        assert(is_dir($queryDir));
        $getClassName = new GetClassName();
        $classes = [];
        /** @var SplFileInfo $file */
        foreach (self::files($queryDir) as $file) {
            $class = ($getClassName)((string) $file->getRealPath());
            if ($class) {
                assert(interface_exists($class));
                $classes[] = $class;
            }
        }

        sort($classes);

        return new self($classes);
    }

    private static function files(string $dir): RegexIterator
    {
        return new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $dir,
                    /** @psalm-suppress ArgumentTypeCoercion */
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+\.php$/',
            RecursiveRegexIterator::MATCH
        );
    }
}
