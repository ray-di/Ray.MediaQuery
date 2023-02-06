<?php

namespace Ray\MediaQuery\Factory;

use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\EntityFactoryInterface;

final class TodoEntityFactory
{
    public static function factory($id, $title): TodoConstruct
    {

        return new TodoConstruct($id, $title);
    }
}
