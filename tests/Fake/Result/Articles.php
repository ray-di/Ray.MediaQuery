<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Override;

use function count;

/**
 * Test fake — not part of the public API.
 *
 * SELECT-path PostQuery result: a typed collection wrapper around the rows
 * pre-hydrated by the framework.
 *
 * The generic parameter `T` is the row shape — typically an entity class
 * (`Articles<Article>`) when `@return Articles<EntityClass>` or a `factory:`
 * attribute resolves to one, or `array<string, mixed>` for the assoc-array
 * fallback. The framework still hands `$context->rows` as `array<mixed>`;
 * the wrapper narrows it to `list<T>` based on the caller's declaration. PHP
 * has no runtime generics, so the narrow is a docblock claim — Psalm and
 * PHPStan honour it through to `foreach`, `->rows[0]`, and derived methods.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 */
final class Articles implements PostQueryInterface, IteratorAggregate, Countable
{
    /** @param list<T> $rows */
    public function __construct(
        public readonly array $rows,
    ) {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        /** @var list<T> $rows */
        $rows = $context->rows;

        return new static($rows);
    }

    /** @return ArrayIterator<int, T> */
    #[Override]
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->rows);
    }

    #[Override]
    public function count(): int
    {
        return count($this->rows);
    }

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }
}
