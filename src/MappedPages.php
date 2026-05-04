<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use Ray\AuraSqlModule\Pagerfanta\Page;

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
        return $this->pages->offsetExists($pageIndex);
    }

    #[Override]
    public function offsetGet(mixed $pageIndex): mixed
    {
        $page = $this->pages->offsetGet($pageIndex);
        $data = $page instanceof Page ? $page->data : null;
        if (! $page instanceof Page || ! is_array($data)) {
            return $page;
        }

        $rowMapper = $this->rowMapper;
        $page = clone $page;
        $page->data = PageRows::map($data, $rowMapper);

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
