<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Support;

use function implode;
use function is_file;
use function rtrim;

/**
 * Resolves a `#[DbQuery]` id to a `{dir}/{id}.sql` file across the configured
 * SQL directories, mirroring SqlQuery::perform()'s `sprintf('%s/%s.sql', ...)`.
 *
 * SQL ids are a flat, globally-unique namespace in Ray.MediaQuery, so a single
 * id is searched across every configured directory.
 */
final class SqlFileResolver
{
    /** @var list<string> */
    private array $directories;

    /** @param list<string> $directories */
    public function __construct(array $directories)
    {
        $this->directories = $directories;
    }

    public function isConfigured(): bool
    {
        return $this->directories !== [];
    }

    public function resolve(string $id): string|null
    {
        foreach ($this->directories as $dir) {
            $path = rtrim($dir, '/') . '/' . $id . '.sql';
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public function describeDirectories(): string
    {
        return implode(', ', $this->directories);
    }
}
