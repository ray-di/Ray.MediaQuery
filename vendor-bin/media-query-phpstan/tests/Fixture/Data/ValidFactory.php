<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Fixture\Data;

use stdClass;

final class ValidFactory
{
    public static function factory(): object
    {
        return new stdClass();
    }
}
