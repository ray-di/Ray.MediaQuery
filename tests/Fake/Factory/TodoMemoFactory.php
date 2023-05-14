<?php

namespace Ray\MediaQuery\Factory;

use Ray\MediaQuery\CsvEnities;
use Ray\MediaQuery\Entity\Memo;
use Ray\MediaQuery\Entity\Todo;

final class TodoMemoFactory
{
    public static function factory(
        string $id,
        string $title,
        string|null $memoIds,
        string|null $memoBodies
    ): Todo
    {
        return new Todo(
            $id,
            $title,
            (new CsvEnities)(Memo::class, $memoIds, $memoBodies)
        );
    }
}
