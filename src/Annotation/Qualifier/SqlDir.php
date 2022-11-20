<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation\Qualifier;

use Attribute;
use Ray\Di\Di\Qualifier;

#[Attribute(Attribute::TARGET_PARAMETER), Qualifier]
final class SqlDir
{
}
