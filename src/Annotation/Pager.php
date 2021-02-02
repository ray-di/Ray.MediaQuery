<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
final class Pager implements NamedArgumentConstructorAnnotation
{
    /** @var int */
    public $perPage;

    /** @var string */
    public $queryKey;

    /** @var string */
    public $template;

    public function __construct(string $queryKey = 'page', int $perPage = 10, string $template = '/{?page}')
    {
        $this->perPage = $perPage;
        $this->queryKey = $queryKey;
        $this->template = $template;
    }
}
