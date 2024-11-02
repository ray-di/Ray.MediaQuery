<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ClassesInDirectories
{
    /**
     * @param list<string> $directories
     * @return Generator<int, class-string>
     */
    public static function list(string ...$directories): Generator
    {
        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory)
            );

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

                if (class_exists($className) || interface_exists($className)) {
                    yield $className;
                }
            }
        }
    }

    private static function getClassFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $namespace = '';
        $class = '';
        $tokens = token_get_all($content);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (!isset($tokens[$i][0])) {
                continue;
            }

            if ($tokens[$i][0] === T_NAMESPACE) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j][0] === T_NAME_QUALIFIED) {
                        $namespace = $tokens[$j][1];
                        break;
                    }
                }
            }

            if ($tokens[$i][0] === T_CLASS || $tokens[$i][0] === T_INTERFACE) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2;
                    }
                }
            }
        }

        if ($class === '') {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }
}
