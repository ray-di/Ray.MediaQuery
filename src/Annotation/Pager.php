<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Pager
{
    /** @param int|string $perPage int:the number of items, string: the name of the argument of the number of items */
    public function __construct(
        public int|string $perPage = 10,
        public string $template = '/{?page}',
    ) {
    }
}
