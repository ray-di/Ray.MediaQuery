<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function assert;
use function class_exists;
use function count;
use function file_get_contents;
use function interface_exists;
use function is_array;
use function is_string;
use function token_get_all;

use const T_CLASS;
use const T_INTERFACE;
use const T_NAME_QUALIFIED;
use const T_NAMESPACE;
use const T_STRING;

final class ClassesInDirectories
{
    /**
     * @param list<string> $directories
     *
     * @return Generator<int, class-string>
     */
    public static function list(string ...$directories): Generator // @phpstan-ignore-line
    {
        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory),
            );

            /** @psalm-suppress MixedAssignment */
            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo) {
                    continue;
                }

                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = self::getClassFromFile($file->getRealPath());
                if ($className === null) {
                    continue;
                }

                if (! class_exists($className) && ! interface_exists($className)) {
                    continue;
                }

                assert(class_exists($className) || interface_exists($className));

                yield $className;
            }
        }
    }

    private static function getClassFromFile(string $filePath): string|null
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $tokens = token_get_all($content);
        /** @var array<int, mixed> $tokens */

        $namespace = self::extractNamespace($tokens);
        $class = self::extractClassName($tokens);

        if ($class === null) {
            return null;
        }

        if ($namespace === null) {
            return $class;
        }

        return $namespace . '\\' . $class;
    }

    /** @param array<int, mixed> $tokens*/
    private static function extractNamespace(array $tokens): string|null
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($tokens as $index => $token) {
            if (is_array($token) && $token[0] !== T_NAMESPACE) {
                continue;
            }

            for ($j = $index + 1, $count = count($tokens); $j < $count; $j++) {
                assert(is_array($tokens[$j]) && isset($tokens[$j][0]));
                if ($tokens[$j][0] === T_NAME_QUALIFIED) {
                    $string = $tokens[$j][1];
                    assert(is_string($string));

                    return $string;
                }
            }
        }

        return null;
    }

    /** @param array<int, mixed> $tokens */
    private static function extractClassName(array $tokens): string|null
    {
        foreach ($tokens as $index => $token) {
            assert(is_array($token));
            if ($token[0] !== T_CLASS && $token[0] !== T_INTERFACE) {
                continue;
            }

            for ($j = $index + 1, $count = count($tokens); $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $string = $tokens[$j][1];
                    assert(is_string($string));

                    return $string;
                }
            }
        }

        return null;
    }
}
