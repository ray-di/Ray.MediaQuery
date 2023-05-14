<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

class Todo
{
    public function __construct(
        public string $id,
        public string $title,

        /** @var Memo[] */
        public array $memos = []
    ) {
    }
}
