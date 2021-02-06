<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayAccess;
use Aura\Sql\ExtendedPdoInterface;
use Countable;
use LogicException;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\ExtendedPdoAdapter;
use Ray\AuraSqlModule\Pagerfanta\Page;

/**
 * @implements ArrayAccess<int, Page>
 */
class Pages implements ArrayAccess, Countable
{
    private AuraSqlPagerInterface $delegate;
    private ExtendedPdoInterface $pdo;
    private string $sql;

    /** @var array<string, mixed> */
    private array $params;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(AuraSqlPagerInterface $pager, ExtendedPdoInterface $pdo, string $sql, array $params)
    {
        $this->delegate = $pager;
        $this->pdo = $pdo;
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * @param int $pageIndex
     */
    public function offsetExists($pageIndex): bool
    {
        return (bool) $this->offsetGet($pageIndex);
    }

    /**
     * @param int $pageIndex
     *
     * @return ?Page
     */
    public function offsetGet($pageIndex): ?Page
    {
        return $this->delegate->offsetGet($pageIndex);
    }

    /**
     * @param int   $offset
     * @param mixed $value
     *
     * @codeCoverageIgnore
     */
    public function offsetSet($offset, $value): void
    {
        unset($offset, $value);

        throw new LogicException('Read only');
    }

    /**
     * @param mixed $offset
     *
     * @codeCoverageIgnore
     */
    public function offsetUnset($offset): void
    {
        unset($offset);

        throw new LogicException('Read only');
    }

    public function count(): int
    {
        return (new ExtendedPdoAdapter($this->pdo, $this->sql, $this->params))->getNbResults();
    }
}
