<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

/** @deprecated Use constructor property promotion with {@see StringCase::camel()} instead. Kept for 1.x backward compatibility. */
trait CamelCaseTrait
{
    public function __set(string $name, mixed $value): void
    {
        $this->{StringCase::camel($name)} = $value;
    }
}
