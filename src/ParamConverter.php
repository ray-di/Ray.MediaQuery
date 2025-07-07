<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;
use Override;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\ToArrayInterface;
use Ray\MediaQuery\Exception\CouldNotBeConvertedException;
use Ray\MediaQuery\Exception\PropertyNameConflictException;
use ReflectionClass;

use function array_intersect;
use function array_keys;
use function assert;
use function enum_exists;
use function function_exists;
use function implode;
use function is_object;
use function method_exists;
use function print_r;
use function property_exists;

final class ParamConverter implements ParamConverterInterface
{
    private const MYSQL_DATETIME = 'Y-m-d H:i:s';

    public function __construct(
        private ToArrayInterface $toArray,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, mixed> $values
     *
     * @param-out array<array-key, mixed> $values
     */
    #[Override]
    public function __invoke(array &$values): void
    {
        $values = $this->expandInputObjects($values);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private function expandInputObjects(array $values): array
    {
        /** @var array<array-key, mixed> $result */
        $result = [];

        foreach ($values as $key => $value) {
            if (! is_object($value)) {
                /** @psalm-suppress MixedAssignment */
                $result[$key] = $value;
                continue;
            }

            if ($this->hasInputAttribute($value)) {
                $flattenedArray = ($this->toArray)($value);
                $this->checkConflict($flattenedArray, $result);
                $result += $flattenedArray;
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $result[$key] = $this->convert($value);
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $flattenedArray
     * @param array<array-key, mixed> $result
     */
    private function checkConflict(array $flattenedArray, array $result): void
    {
        // Check for conflicts with existing keys in the result
        $resultKeys = array_keys($result);
        $flattenedAKeys = array_keys($flattenedArray);
        $conflicts = array_intersect($resultKeys, $flattenedAKeys);
        if ($conflicts !== []) {
            throw new PropertyNameConflictException(implode(',', $conflicts));
        }
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

    public function convert(mixed $value): mixed
    {
        assert(is_object($value), 'convert() expects an object value');
        
        if ($value instanceof DateTimeInterface) {
            return $value->format(self::MYSQL_DATETIME);
        }

        if ($value instanceof ToScalarInterface) {
            return $value->toScalar();
        }

        if (method_exists($value, '__toString')) {
            return (string) $value;
        }

        $isEnumUnavailable = ! function_exists('enum_exists') || ! enum_exists($value::class);
        if ($isEnumUnavailable) {
            throw new CouldNotBeConvertedException(print_r($value, true));
        }

        if (method_exists($value, 'from') && method_exists($value, 'tryFrom')) {
            assert(property_exists($value, 'value'));

            return $value->value;
        }

        assert(property_exists($value, 'name'));

        return $value->name;
    }
}
