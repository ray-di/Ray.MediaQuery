<?php

namespace Ray\MediaQuery\Factory;

use Ray\MediaQuery\CsvEnities;
use Ray\MediaQuery\Entity\Memo;
use Ray\MediaQuery\Entity\TodoConstruct;

final class TodoMemoFactory
{
    public static function factory(
        string $id,
        string $title,
        string|null $memoIds,
        string|null $memoBodies
    ): TodoConstruct
    {
        return new TodoConstruct(
            $id,
            $title,
            (new CsvEnities)(Memo::class, $memoIds, $memoBodies)
        );
    }
}
