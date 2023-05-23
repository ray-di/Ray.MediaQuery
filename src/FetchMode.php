<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;
use Ray\MediaQuery\Annotation\DbQuery;
use ReflectionNamedType;
use ReflectionUnionType;
use function class_exists;
use function is_callable;
use function method_exists;

final class FetchMode
{
    /**
     * @param int                                   $mode
     * @param array{0:string, 1:string}|string|null $args
     */
    public function __construct(
        public int $mode,
        public array|string|null $args,
    ) {
    }
}
