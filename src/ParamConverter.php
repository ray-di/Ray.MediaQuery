<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;
use Ray\MediaQuery\Exception\CouldNotBeConvertedException;

use function assert;
use function enum_exists;
use function function_exists;
use function is_object;
use function method_exists;
use function print_r;
use function property_exists;

final class ParamConverter implements ParamConverterInterface
{
    private const MYSQL_DATETIME = 'Y-m-d H:i:s';

    /**
     * {@inheritDoc}
     */
    public function __invoke(array &$values): void
    {
        /** @psalm-suppress MixedAssignment $value */
        foreach ($values as &$value) {
            if (! is_object($value)) {
                continue;
            }

            if ($value instanceof DateTimeInterface) {
                $value = $value->format(self::MYSQL_DATETIME);
                continue;
            }

            if (method_exists($value, '__toString')) {
                $value = (string) $value;
                continue;
            }

            if ($value instanceof ToScalarInterface) {
                $value = $value->toScalar();
                continue;
            }

            $isEnumUnavailable = ! function_exists('enum_exists') || ! enum_exists($value::class);
            if ($isEnumUnavailable) {
                throw new CouldNotBeConvertedException(print_r($value, true));
            }

            if (method_exists($value, 'from') && method_exists($value, 'tryFrom')) {
                assert(property_exists($value, 'value'));
                $value = $value->value;
                continue;
            }

            assert(property_exists($value, 'name'));
            $value = $value->name;
        }
    }
}
