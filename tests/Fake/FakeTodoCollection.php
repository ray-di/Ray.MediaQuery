<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Minimal domain-specific collection for testing Collection return type auto-wrap.
 *
 * @implements IteratorAggregate<int, mixed>
 */
final class FakeTodoCollection implements IteratorAggregate, Countable
{
    public function __construct(public readonly array $items)
    {
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
