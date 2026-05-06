<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\PseudoTypes\Generic;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Object_;
use Ray\MediaQuery\Result\PostQueryInterface;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;

use function array_key_last;
use function assert;
use function class_exists;
use function is_a;
use function substr;

final class ReturnEntity implements ReturnEntityInterface
{
    public function __construct(
        private DocBlockFactoryInterface $docBlockFactory,
    ) {
    }

    /** @inheritDoc  */
    #[Override]
    public function __invoke(ReflectionMethod $method): string|null
    {
        $returnType = $method->getReturnType();
        if ($returnType === null) {
            return null;
        }

        $returnTypeClass = $this->getReturnTypeName($returnType);

        if (
            class_exists($returnTypeClass)
            && ! is_a($returnTypeClass, PagesInterface::class, true)
            && ! is_a($returnTypeClass, PostQueryInterface::class, true)
        ) {
            return $returnTypeClass;
        }

        return $this->docblock($method);
    }

    private function getReturnTypeName(ReflectionType $reflectionType): string
    {
        if ($reflectionType instanceof ReflectionNamedType) {
            return $reflectionType->getName();
        }

        return (string) $reflectionType;
    }

    private function extractValueType(Type|null $type): Type|null
    {
        if ($type instanceof Array_) {
            return $type->getValueType();
        }

        if ($type instanceof Generic) {
            $types = $type->getTypes();
            $lastKey = array_key_last($types);

            return $lastKey !== null ? $types[$lastKey] : null;
        }

        return null;
    }

    /** @return ?class-string  */
    private function docblock(ReflectionMethod $method): string|null
    {
        $context = (new ContextFactory())->createFromReflector($method);
        $docComment = $method->getDocComment();
        if ($docComment === false) {
            return null;
        }

        $docblock = $this->docBlockFactory->create($docComment, $context);
        $returns = $docblock->getTagsByName('return');
        if (! isset($returns[0])) {
            return null;
        }

        $return = $returns[0];
        assert($return instanceof Return_);
        $type = $return->getType();

        $valueType = $this->extractValueType($type);
        if (! $valueType instanceof Object_) {
            return null;
        }

        $fqsen = (string) $valueType->getFqsen();

        $classString = substr($fqsen, 1);
        assert(class_exists($classString));

        return $classString;
    }
}
