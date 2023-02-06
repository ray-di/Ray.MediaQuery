<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Object_;
use ReflectionMethod;

use function assert;
use function class_exists;
use function substr;

final class ReturnEntity implements ReturnEntityInterface
{
    /** @inheritDoc  */
    public function __invoke(ReflectionMethod $method): string|null
    {
        $returnType = $method->getReturnType();
        if ($returnType === null) {
            return null;
        }

        $returnTypeClass = (string) $returnType;
        if (class_exists($returnTypeClass) && $returnTypeClass !== Pages::class) {
            return $returnTypeClass;
        }

        return $this->docblock($method);
    }

    /** @return ?class-string  */
    private function docblock(ReflectionMethod $method): string|null
    {
        $factory = DocBlockFactory::createInstance();
        $context = (new ContextFactory())->createFromReflector($method);
        $docComment = $method->getDocComment();
        if ($docComment === false) {
            return null;
        }

        $docblock = $factory->create($docComment, $context);
        $returns = $docblock->getTagsByName('return');
        if (! isset($returns[0])) {
            return null;
        }

        $return = $returns[0];
        assert($return instanceof Return_);
        $type = $return->getType();
        if (! $type instanceof Array_ && ! $type instanceof Collection) {
            return null;
        }

        $valueType = $type->getValueType();
        if (! $valueType instanceof Object_) {
            return null;
        }

        $fqsen = (string) $valueType->getFqsen();

        $classString = substr($fqsen, 1);
        assert(class_exists($classString));

        return $classString;
    }
}
