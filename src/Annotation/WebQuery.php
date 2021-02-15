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
final class WebQuery implements NamedArgumentConstructorAnnotation
{
    /** @var string */
    public $method;

    /** @var string */
    public $uri;

    public function __construct(string $method, string $uri)
    {
        $this->method = $method;
        $this->uri = $uri;
    }
}
