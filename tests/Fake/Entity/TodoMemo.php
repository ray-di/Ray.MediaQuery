<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

use Ray\MediaQuery\CsvEnities;

class TodoMemo
{
    /** @var array<Memo> */
    public array $memos;

    public function __construct(
        public string $id,
        public string $title,
        string|null $memoIds,
        string|null $memoBodies
    ){
        $this->memos = (new CsvEnities())(Memo::class, $memoIds, $memoBodies);
    }
}
