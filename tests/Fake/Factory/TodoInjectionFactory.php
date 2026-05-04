<?php

namespace Ray\MediaQuery\Factory;

use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\EntityFactoryInterface;

final class TodoInjectionFactory
{
    public static int $instances = 0;

    public function __construct(
        private FakeFactoryHelperInterface $helper,
    ) {
        self::$instances++;
    }

    public static function resetInstances(): void
    {
        self::$instances = 0;
    }

    public function factory($id, $title): TodoConstruct
    {
        return new TodoConstruct($id, $this->helper->help($title));
    }
}
