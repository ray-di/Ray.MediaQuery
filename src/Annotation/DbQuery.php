<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function assert;
use function class_exists;

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

    /** @var ?class-string */
    public $entity;

    /**
     * @Enum({"row", "row_list"})
     * @var 'row'|'row_list'
     */
    public $type = 'row_list';

    /**
     * @param 'row'|'row_list' $type
     */
    public function __construct(string $id, string $type = 'row_list', ?string $entity = null)
    {
        assert($entity === null || class_exists($entity));
        $this->id = $id;
        $this->entity = $entity;
        $this->type = $type;
    }
}
