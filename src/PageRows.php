<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use function array_map;
use function is_array;

final class PageRows
{
    /** @codeCoverageIgnore */
    private function __construct()
    {
    }

    /**
     * @param array<array-key, mixed>                  $rows
     * @param callable(array<array-key, mixed>): mixed $rowMapper
     *
     * @return array<array-key, mixed>
     */
    public static function map(array $rows, callable $rowMapper): array
    {
        return array_map(
            static function (mixed $row) use ($rowMapper): mixed {
                return is_array($row) ? $rowMapper($row) : $row;
            },
            $rows,
        );
    }
}
