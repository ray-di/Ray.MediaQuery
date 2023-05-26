<?php

namespace Ray\MediaQuery\Factory;

use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\EntityFactoryInterface;

final class TodoInjectionFactory
{
    public function __construct(
        private FakeFactoryHelperInterface $helper
    ){
    }

    public function factory($id, $title): TodoConstruct
    {
        return new TodoConstruct($id, $this->helper->help($title));
    }
}
