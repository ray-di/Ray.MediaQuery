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

    /** @param array<string, mixed> $values */
    public function __invoke(string $queryId, array $values, Pager $pager, string|null $entity): PagesInterface
    {
        if (is_string($pager->perPage)) {
            $values = $this->dynamicPager($pager, $values);
        }

        assert(is_int($pager->perPage));
        $this->logger->start();
        /** @var ?class-string $entity */
        $result = $this->sqlQuery->getPages($queryId, $values, $pager->perPage, $pager->template, $entity);
        $this->logger->log($queryId, $values);

        return $result;
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
