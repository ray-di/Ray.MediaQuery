<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @Target("METHOD")
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DbQuery
{
    /** @param 'row'|'row_list' $type */
    public function __construct(
        public string $id,
        public string $type = 'row_list',
        public string $factory = '',
    ) {
    }
}
