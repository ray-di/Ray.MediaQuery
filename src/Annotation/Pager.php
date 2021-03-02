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
final class Pager
{
    /** @var int */
    public $perPage;

    /** @var string */
    public $template;

    public function __construct(int $perPage = 10, string $template = '/{?page}')
    {
        $this->perPage = $perPage;
        $this->template = $template;
    }
}
