<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Rules\Data;

use stdClass;

final class FactoryWithoutFactoryMethod
{
    public function create(): object
    {
        return new stdClass();
    }
}
