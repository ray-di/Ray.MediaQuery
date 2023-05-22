<?php

namespace Ray\MediaQuery\Factory;

use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\EntityFactoryInterface;

interface FakeFactoryHelperInterface
{
    public function help(string $string): string;
}
