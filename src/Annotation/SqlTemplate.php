<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;

/**
 * Qualifies the template that wraps each executed SQL with its query ID for the SQL log
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class SqlTemplate
{
}
