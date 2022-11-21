<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Exception\InvalidPerPageVarNameException;
use Ray\MediaQuery\Exception\PerPageNotIntTypeException;

use function assert;
use function class_exists;
use function is_int;
use function is_string;
use function method_exists;

class DbQueryInterceptor implements MethodInterceptor
{
    public function __construct(
        private SqlQueryInterface $sqlQuery,
        private MediaQueryLoggerInterface $logger,
        private ParamInjectorInterface $paramInjector,
    ) {
    }

    /** @return array<mixed>|object|null */
    public function invoke(MethodInvocation $invocation): array|object|null
    {
        $method = $invocation->getMethod();
        /** @var DbQuery $dbQuery */
        $dbQuery = $method->getAnnotation(DbQuery::class);
        $pager = $method->getAnnotation(Pager::class);
        $values = $this->paramInjector->getArgumentes($invocation);
        if ($pager instanceof Pager) {
            return $this->getPager($dbQuery->id, $values, $pager, $dbQuery->entity);
        }

        $fetchStyle = $this->getFetchMode($dbQuery);

        return $this->sqlQuery($dbQuery, $values, $fetchStyle, (string) $dbQuery->entity);
    }

    /** @return  PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchStyle */
    private function getFetchMode(DbQuery $dbQuery): int
    {
        if (! class_exists((string) $dbQuery->entity)) {
            return PDO::FETCH_ASSOC;
        }

        if (is_string($dbQuery->entity) && method_exists($dbQuery->entity, '__construct')) {
            return PDO::FETCH_FUNC;
        }

        return PDO::FETCH_CLASS;
    }

    /**
     * @param array<string, mixed>                              $values
     * @param PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchStyle
     *
     * @return array<mixed>|object|null
     */
    private function sqlQuery(DbQuery $dbQuery, array $values, int $fetchStyle, int|string|callable $fetchArg): array|object|null
    {
        if ($dbQuery->type === 'row') {
            return $this->sqlQuery->getRow($dbQuery->id, $values, $fetchStyle, $fetchArg);
        }

        return $this->sqlQuery->getRowList($dbQuery->id, $values, $fetchStyle, $fetchArg);
    }

    /** @param array<string, mixed> $values */
    private function getPager(string $queryId, array $values, Pager $pager, string|null $entity): PagesInterface
    {
        if (is_string($pager->perPage)) {
            $values = $this->getDynamicPerPage($pager, $values);
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
    private function getDynamicPerPage(Pager $pager, array $values): array
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
