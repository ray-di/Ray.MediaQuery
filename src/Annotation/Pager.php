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
    /** @var int|string */
    public $perPage;

    /** @var string */
    public $template;

    /**
     * @param int|string $perPage int:the number of items, string: the name of the argument of the number of items
     */
    public function __construct($perPage = 10, string $template = '/{?page}')
    {
        $this->perPage = $perPage;
        $this->template = $template;
    }
}
