<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Support;

use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\ClassMethod;

use function ltrim;

/**
 * Locates a PHP attribute on a parsed method by fully-qualified class name.
 *
 * PHPStan analyses a name-resolved AST, so attribute `Name` nodes already carry
 * the fully-qualified name; we still normalise the leading backslash to be safe.
 */
final class AttributeFinder
{
    public static function find(ClassMethod $method, string $fqcn): Attribute|null
    {
        $needle = ltrim($fqcn, '\\');
        foreach ($method->attrGroups as $group) {
            foreach ($group->attrs as $attr) {
                if (ltrim($attr->name->toString(), '\\') === $needle) {
                    return $attr;
                }
            }
        }

        return null;
    }

    public static function has(ClassMethod $method, string $fqcn): bool
    {
        return self::find($method, $fqcn) !== null;
    }
}
