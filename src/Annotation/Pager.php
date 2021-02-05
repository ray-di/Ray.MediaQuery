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
final class Pager implements NamedArgumentConstructorAnnotation
{
    /** @var int */
    public $perPage;

    /** @var string */
    public $queryKey;

    /** @var string */
    public $template;

    public function __construct(int $perPage = 10, string $template = '/{?page}')
    {
        $this->perPage = $perPage;
        $this->template = $template;
    }
}
