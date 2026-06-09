<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Support;

use function getcwd;
use function is_file;
use function ltrim;
use function preg_match;
use function rtrim;
use function sprintf;

final class SqlFileResolver
{
    /** @param list<string> $sqlDirectories */
    public function __construct(
        private readonly array $sqlDirectories,
    ) {
    }

    public function resolve(string $sqlId): string|null
    {
        foreach ($this->sqlDirectories as $sqlDirectory) {
            $directory = $this->absolutePath($sqlDirectory);
            $sqlFile = sprintf('%s/%s.sql', rtrim($directory, '/'), $sqlId);
            if (is_file($sqlFile)) {
                return $sqlFile;
            }
        }

        return null;
    }

    private function absolutePath(string $path): string
    {
        if ($path === '') {
            return (string) getcwd();
        }

        if (preg_match('#^([A-Za-z]:)?/#', $path) === 1) {
            return $path;
        }

        return (string) getcwd() . '/' . ltrim($path, '/');
    }
}
