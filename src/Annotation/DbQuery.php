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
    /** @var string */
    public $id;

    /** @var string */
    public $entity;

    public function __construct(string $id, string $entity = '')
    {
        $this->id = $id;
        $this->entity = $entity;
    }
}
