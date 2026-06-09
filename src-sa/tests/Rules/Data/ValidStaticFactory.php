<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Rules\Data;

use stdClass;

final class ValidStaticFactory
{
    public static function factory(): object
    {
        return new stdClass();
    }
}
