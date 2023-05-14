<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

use Koriym\CsvEntities\CsvEntities;

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
        $this->memos = (new CsvEntities())(Memo::class, $memoIds, $memoBodies);
    }
}
