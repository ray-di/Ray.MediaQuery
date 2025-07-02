<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\InputQuery\Attribute\Input;
use Ray\MediaQuery\Exception\PropertyNameConflictException;
use ReflectionClass;
use ReflectionProperty;

use function array_merge;
use function is_object;

final class EnhancedParamConverter implements ParamConverterInterface
{
    public function __construct(
        private ParamConverterInterface $baseConverter,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(array &$values): void
    {
        // 1. Expand Input attribute objects to flat structure
        $this->expandInputObjects($values);

        // 2. Apply existing ParamConverter processing
        ($this->baseConverter)($values);
    }

    /** @param array<string, mixed> $values */
    private function expandInputObjects(array &$values): void
    {
        $expanded = [];
        $usedNames = [];

        foreach ($values as $key => $value) {
            if (is_object($value) && $this->hasInputAttribute($value)) {
                // Expand Input attribute objects to flat structure
                $flatProps = $this->flattenInputObject($value, $usedNames);
                $expanded = array_merge($expanded, $flatProps);
            } else {
                $expanded[$key] = $value;
            }
        }

        $values = $expanded;
    }

    /**
     * @param array<string, bool> $usedNames
     *
     * @return array<string, mixed>
     */
    private function flattenInputObject(object $obj, array &$usedNames): array
    {
        $result = [];
        $reflection = new ReflectionClass($obj);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propName = $property->getName();
            $propValue = $property->getValue($obj);

            if (is_object($propValue) && $this->hasInputAttribute($propValue)) {
                // Recursively expand nested Input attribute objects (ignore hierarchy)
                $nestedProps = $this->flattenInputObject($propValue, $usedNames);
                $result = array_merge($result, $nestedProps);
            } else {
                // Check for property name conflicts
                if (isset($usedNames[$propName])) {
                    throw new PropertyNameConflictException($propName);
                }

                $usedNames[$propName] = true;
                $result[$propName] = $propValue;
            }
        }

        return $result;
    }

    private function hasInputAttribute(object $obj): bool
    {
        $reflection = new ReflectionClass($obj);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return false;
        }

        // Check if constructor parameters have #[Input] attribute
        foreach ($constructor->getParameters() as $param) {
            $inputAttrs = $param->getAttributes(Input::class);
            if ($inputAttrs !== []) {
                return true;
            }
        }

        return false;
    }
}
