<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use FilesystemIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

use function assert;
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
        foreach (self::files($queryDir) as $file) {
            $class = ($getClassName)($file->getRealPath());
            if ($class) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return new self($classes);
    }

    private static function files(string $dir): Iterator
    {
        return new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $dir,
                    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+\.php$/',
            RecursiveRegexIterator::MATCH
        );
    }
}
