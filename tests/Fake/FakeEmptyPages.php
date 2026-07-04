<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayIterator;

final class FakeEmptyPages implements PagesInterface
{
    /** @var ArrayIterator<int, mixed> */
    private ArrayIterator $iter;

    public function __construct()
    {
        $this->iter = new ArrayIterator([]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->iter->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->iter->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->iter->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->iter->offsetUnset($offset);
    }

    public function count(): int
    {
        return $this->iter->count();
    }

    public function getNbPages(): int
    {
        return 0;
    }
}
