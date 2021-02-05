<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DbQuery implements NamedArgumentConstructorAnnotation
{
    /** @var string */
    public $id;

    public function __construct(string $id = '')
    {
        $this->id = $id;
    }
}
