<?php

namespace Ray\MediaQuery\Factory;

use Ray\MediaQuery\Entity\Memo;
use Ray\MediaQuery\Entity\Todo;

final class TodoMemoFactory
{
    public static function factory(
        string $id,
        string $title,
        string|null $memoIds,
        string|null $memoBodies
    ): Todo {
        $memoIds ??= '';
        $memoBodies ??= '';
        $arrays = array_map(fn($csv) => $csv === '' ? [] : explode(',', $csv), [$memoIds, $memoBodies]);
        $length = count($arrays[0]);
        $memos = [];
        for($i = 0; $i < $length; $i++) {
            $args = array_column($arrays, $i);
            $memos[] = new Memo(...$args);
        }

        return new Todo($id, $title, $memos);
    }
}
