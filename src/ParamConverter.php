<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;
use Ray\MediaQuery\Exception\CouldNotBeConvertedException;

use function is_object;
use function method_exists;
use function print_r;

class ParamConverter implements ParamConverterInterface
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

            throw new CouldNotBeConvertedException(print_r($value, true));
        }
    }
}
