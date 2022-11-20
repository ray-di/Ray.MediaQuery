<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class WebQuery
{
    public function __construct(
        public string $id,
    ) {
    }
}
