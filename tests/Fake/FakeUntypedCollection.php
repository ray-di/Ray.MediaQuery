<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Laravel-style collection with an untyped constructor parameter.
 *
 * @implements IteratorAggregate<int, mixed>
 */
final class FakeUntypedCollection implements IteratorAggregate, Countable
{
    /** @var array<mixed> */
    public readonly array $items;

    /** @param mixed $items */
    public function __construct($items = [])
    {
        $this->items = (array) $items;
    }

    /** @return Traversable<int, mixed> */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    #[\Override]
    public function count(): int
    {
        return count($this->items);
    }
}
