<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\ExtendedPdoAdapter;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\Exception\LogicException;

class Pages implements PagesInterface
{
    /** @var AuraSqlPagerInterface  */
    private $delegate;

    /** @var ExtendedPdoInterface */
    private $pdo;

    /** @var string */
    private $sql;

    /** @var array<string, mixed> */
    private $params;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(AuraSqlPagerInterface $pager, ExtendedPdoInterface $pdo, string $sql, array $values)
    {
        $this->delegate = $pager;
        $this->pdo = $pdo;
        $this->sql = $sql;
        $this->params = $values;
    }

    public function offsetExists(int $pageIndex): bool
    {
        return (bool) $this->offsetGet($pageIndex);
    }

    public function offsetGet(int $pageIndex): ?Page
    {
        return $this->delegate->offsetGet($pageIndex);
    }

    /**
     * @param int   $offset
     * @param mixed $value
     *
     * @codeCoverageIgnore
     */
    public function offsetSet(int $offset, $value): void
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
