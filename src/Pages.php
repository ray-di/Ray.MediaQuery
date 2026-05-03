<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use Override;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\ExtendedPdoAdapter;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\Exception\LogicException;

use function array_map;
use function is_array;

/** @template T of class-string|mixed */
final class Pages implements PagesInterface
{
    /** @var (callable(array<array-key, mixed>): mixed)|null */
    private $rowMapper;

    /**
     * @param array<string, mixed>                            $params
     * @param (callable(array<array-key, mixed>): mixed)|null $rowMapper
     */
    public function __construct(
        private AuraSqlPagerInterface $delegate,
        private ExtendedPdoInterface $pdo,
        private string $sql,
        private array $params,
        callable|null $rowMapper = null,
    ) {
        $this->rowMapper = $rowMapper;
    }

    /**
     * @param callable(array<array-key, mixed>): mixed $rowMapper
     *
     * @return self<T>
     */
    public function withRowMapper(callable $rowMapper): self
    {
        $this->rowMapper = $rowMapper;

        return $this;
    }

    #[Override]
    public function offsetExists($pageIndex): bool
    {
        return (bool) $this->offsetGet($pageIndex);
    }

    #[Override]
    public function offsetGet($pageIndex): Page|null
    {
        $page = $this->delegate->offsetGet($pageIndex);
        if (! $page instanceof Page || $this->rowMapper === null || ! is_array($page->data)) {
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
        return (new ExtendedPdoAdapter($this->pdo, $this->sql, $this->params))->getNbResults();
    }
}
