<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

final class MappedPagesFakePages implements PagesInterface
{
    public mixed $setValue = null;
    public bool $unsetCalled = false;

    public function __construct(
        private mixed $value,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        unset($offset);

        return true;
    }

    public function offsetGet(mixed $offset): mixed
    {
        unset($offset);

        return $this->value;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        unset($offset);

        $this->setValue = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($offset);

        $this->unsetCalled = true;
    }

    public function count(): int
    {
        return 1;
    }

    public function getNbPages(): int
    {
        return 1;
    }
}
