<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use Override;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\ExtendedPdoAdapter;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\Exception\InvalidPerPageException;
use Ray\MediaQuery\Exception\LogicException;

use function ceil;
use function is_array;
use function max;

/** @template T of class-string|mixed */
final class Pages implements PagesInterface
{
    /** @var (callable(array<array-key, mixed>): mixed)|null */
    private $rowMapper;

    /** Memoized result count, shared by count() and getNbPages() as in Pagerfanta */
    private int|null $nbResults = null;

    /**
     * @param array<string, mixed>                            $params
     * @param (callable(array<array-key, mixed>): mixed)|null $rowMapper
     */
    public function __construct(
        private AuraSqlPagerInterface $delegate,
        private ExtendedPdoInterface $pdo,
        private string $sql,
        private array $params,
        private int $perPage,
        callable|null $rowMapper = null,
    ) {
        if ($this->perPage < 1) {
            throw new InvalidPerPageException((string) $this->perPage);
        }

        $this->rowMapper = $rowMapper;
    }

    /** @param callable(array<array-key, mixed>): mixed $rowMapper */
    public function setRowMapper(callable $rowMapper): void
    {
        $this->rowMapper = $rowMapper;
    }

    #[Override]
    public function offsetExists($pageIndex): bool
    {
        // AuraSqlPager::offsetExists() is unsupported, so existence follows offsetGet().
        return (bool) $this->offsetGet($pageIndex);
    }

    #[Override]
    public function offsetGet($pageIndex): Page|null
    {
        $page = $this->delegate->offsetGet($pageIndex);
        $data = $page instanceof Page ? $page->data : null;
        if (! $page instanceof Page || $this->rowMapper === null || ! is_array($data)) {
            return $page;
        }

        $rowMapper = $this->rowMapper;
        $page = clone $page;
        $page->data = PageRows::map($data, $rowMapper);

        return $page;
    }

    /**
     * @param int $offset
     *
     * @return never
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        unset($offset, $value);

        throw new LogicException('Read only');
    }

    /**
     * @return never
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($offset);

        throw new LogicException('Read only');
    }

    #[Override]
    public function count(): int
    {
        return $this->nbResults ??= (new ExtendedPdoAdapter($this->pdo, $this->sql, $this->params))->getNbResults();
    }

    #[Override]
    public function getNbPages(): int
    {
        return max(1, (int) ceil($this->count() / $this->perPage));
    }
}
