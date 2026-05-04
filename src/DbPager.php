<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Exception\InvalidPerPageVarNameException;
use Ray\MediaQuery\Exception\PerPageNotIntTypeException;

use function assert;
use function is_int;
use function is_string;

final class DbPager
{
    public function __construct(
        private MediaQueryLoggerInterface $logger,
        private SqlQueryInterface $sqlQuery,
    ) {
    }

    /**
     * @param array<string, mixed>                            $values
     * @param (callable(array<array-key, mixed>): mixed)|null $rowMapper
     */
    public function __invoke(string $queryId, array $values, Pager $pager, string|null $entity, callable|null $rowMapper = null): PagesInterface
    {
        // Clone the Pager attribute to avoid mutating the caller's instance in dynamicPager().
        $pager = clone $pager;
        if (is_string($pager->perPage)) {
            $values = $this->dynamicPager($pager, $values);
        }

        assert(is_int($pager->perPage));
        $this->logger->start();
        /** @var ?class-string $entity */
        $result = $this->sqlQuery->getPages($queryId, $values, $pager->perPage, $pager->template, $rowMapper === null ? $entity : null);
        $this->logger->log($queryId, $values);

        if ($rowMapper === null) {
            return $result;
        }

        if ($result instanceof Pages) {
            $result->setRowMapper($rowMapper);

            return $result;
        }

        // Keep the wrapper fallback for custom SqlQueryInterface implementations that do not return Pages.
        return new MappedPages($result, $rowMapper);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function dynamicPager(Pager $pager, array $values): array
    {
        $perPage = $pager->perPage;
        if (! isset($values[$perPage])) {
            throw new InvalidPerPageVarNameException((string) $perPage);
        }

        if (! is_int($values[$perPage])) {
            throw new PerPageNotIntTypeException((string) $perPage);
        }

        $perPageInValues = $values[$perPage];
        $pager->perPage = $perPageInValues;
        unset($values[$perPage]);

        return $values;
    }
}
