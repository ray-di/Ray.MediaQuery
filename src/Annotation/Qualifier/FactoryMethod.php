<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation\Qualifier;

use Attribute;
use Ray\Di\Di\Qualifier;

/**
 * Qualifies the name of the method called on the `factory` class of #[DbQuery]
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
#[Qualifier]
final class FactoryMethod
{
}
