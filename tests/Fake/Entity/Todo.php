<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

class Todo
{
    /** @var string */
    public $id;

    /** @var string */
    public $title;

    /** @var Memo[] */
    public $memos = [];
}
