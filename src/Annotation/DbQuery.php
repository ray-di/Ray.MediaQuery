<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class DbQuery
{
    /** @param 'row'|'row_list' $type */
    public function __construct(
        public string $id,
        public string $type = 'row_list',
        /** @var ?class-string */
        public string|null $entity = null,
    ) {
    }
}
