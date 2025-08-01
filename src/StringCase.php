<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use function lcfirst;
use function preg_replace;
use function str_replace;
use function strtolower;
use function ucwords;

final class StringCase
{
    /**
     * Convert snake_case to camelCase
     */
    public static function camel(string $snakeName): string
    {
        return lcfirst(str_replace('_', '', ucwords($snakeName, '_')));
    }

    /**
     * Convert camelCase to snake_case
     */
    public static function snake(string $camelName): string
    {
        $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $camelName);

        return strtolower($result ?? $camelName);
    }
}
