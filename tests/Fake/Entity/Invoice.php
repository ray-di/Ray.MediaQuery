<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

use Ray\MediaQuery\CamelCaseTrait;

class Invoice
{
    use CamelCaseTrait;

    /** @var string */
    public $userName;
}