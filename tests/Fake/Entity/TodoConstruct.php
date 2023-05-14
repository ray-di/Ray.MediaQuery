<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

class TodoConstruct
{
    public function __construct(
        public string $id,
        public string $title,
    ){
    }
}
