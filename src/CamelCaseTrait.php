<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use function lcfirst;
use function str_replace;
use function ucwords;

trait CamelCaseTrait
{
    /**
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $propName =  lcfirst(str_replace('_', '', ucwords($name, '_')));
        $this->{$propName} = $value;
    }
}
