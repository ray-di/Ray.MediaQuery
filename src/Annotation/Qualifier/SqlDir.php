<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation\Qualifier;

use Attribute;
use Ray\Di\Di\Qualifier;

/**
 * Qualifies the path of the directory that holds the SQL files
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
#[Qualifier]
final class SqlDir
{
}
