<?php

namespace Ray\MediaQuery\Factory;

use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\EntityFactoryInterface;
use function ucwords;

class FakeFactoryHelper implements FakeFactoryHelperInterface
{
    public function help(string $string): string
    {
        return strtoupper($string);
    }
}
