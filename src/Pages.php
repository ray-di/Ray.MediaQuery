<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use Override;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\ExtendedPdoAdapter;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\Exception\LogicException;

/** @template T of class-string|mixed */
final class Pages implements PagesInterface
{
    /** @param array<string, mixed> $params */
    public function __construct(
        private AuraSqlPagerInterface $delegate,
        private ExtendedPdoInterface $pdo,
        private string $sql,
        private array $params,
    ) {
    }

    #[Override]
    public function offsetExists($pageIndex): bool
    {
        return (bool) $this->offsetGet($pageIndex);
    }

    #[Override]
    public function offsetGet($pageIndex): Page|null
    {
        return $this->delegate->offsetGet($pageIndex);
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
