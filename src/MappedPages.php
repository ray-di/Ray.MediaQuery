<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use Ray\AuraSqlModule\Pagerfanta\Page;

use function array_map;
use function is_array;

final class MappedPages implements PagesInterface
{
    /** @var callable(array<array-key, mixed>): mixed */
    private $rowMapper;

    /** @param callable(array<array-key, mixed>): mixed $rowMapper */
    public function __construct(
        private PagesInterface $pages,
        callable $rowMapper,
    ) {
        $this->rowMapper = $rowMapper;
    }

    #[Override]
    public function offsetExists(mixed $pageIndex): bool
    {
        return (bool) $this->offsetGet($pageIndex);
    }

    #[Override]
    public function offsetGet(mixed $pageIndex): mixed
    {
        $page = $this->pages->offsetGet($pageIndex);
        if (! $page instanceof Page || ! is_array($page->data)) {
            return $page;
        }

        $rowMapper = $this->rowMapper;
        $page->data = array_map(
            static function (mixed $row) use ($rowMapper): mixed {
                return is_array($row) ? $rowMapper($row) : $row;
            },
            $page->data,
        );

        return $page;
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->pages->offsetSet($offset, $value);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        $this->pages->offsetUnset($offset);
    }

    #[Override]
    public function count(): int
    {
        return $this->pages->count();
    }
}
