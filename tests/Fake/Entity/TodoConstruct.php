<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

class TodoConstruct
{
    /** @var string */
    public $id;

    /** @var string */
    public $title;

    public function __construct(string $id, string $title)
    {
        $this->id = $id;
        $this->title = $title;
    }
}