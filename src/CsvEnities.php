<?php

namespace Ray\MediaQuery;


use function array_map;

final class CsvEnities
{
    public function __invoke($className, ...$csvs): array
    {
        $arrays = array_map(fn($csv) => empty($csv) ? [] : explode(',', $csv), [...$csvs]);
        $length = count($arrays[0]);
        $entities = [];
        for ($i = 0; $i < $length; $i++) {
            $args = array_column($arrays, $i);
            $entities[] = new $className(...$args);
        }

        return $entities;
    }
}
