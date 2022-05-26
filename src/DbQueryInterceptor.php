<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;

use function class_exists;
use function method_exists;

class DbQueryInterceptor implements MethodInterceptor
{
    /** @var SqlQueryInterface */
    private $sqlQuery;

    /** @var MediaQueryLoggerInterface */
    private $logger;

    /** @var ParamInjectorInterface  */
    private $paramInjector;

    public function __construct(SqlQueryInterface $sqlQuery, MediaQueryLoggerInterface $logger, ParamInjectorInterface $paramInjector)
    {
        $this->sqlQuery = $sqlQuery;
        $this->logger = $logger;
        $this->paramInjector = $paramInjector;
    }

    /**
     * @return array<mixed>|object|PagesInterface|null
     */
    public function invoke(MethodInvocation $invocation)
    {
        $method = $invocation->getMethod();
        /** @var DbQuery $dbQury */
        $dbQury = $method->getAnnotation(DbQuery::class);
        $pager = $method->getAnnotation(Pager::class);
        $values = $this->paramInjector->getArgumentes($invocation);
        if ($pager instanceof Pager) {
            return $this->getPager($dbQury->id, $values, $pager);
        }

        $fetchStyle = $this->getFetchMode($dbQury);

        return $this->sqlQuery($dbQury, $values, $fetchStyle, $dbQury->entity);
    }

    /**
     * @return  PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchStyle
     */
    private function getFetchMode(DbQuery $dbQuery): int
    {
        if (! class_exists($dbQuery->entity)) {
            return PDO::FETCH_ASSOC;
        }

        if (method_exists($dbQuery->entity, '__construct')) {
            return PDO::FETCH_FUNC;
        }

        return PDO::FETCH_CLASS;
    }

    /**
     * @param array<string, mixed>                              $values
     * @param PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchStyle
     * @param int|string|callable                               $fetchArg
     *
     * @return array<mixed>|object|null
     */
    private function sqlQuery(DbQuery $dbQuery, array $values, int $fetchStyle, $fetchArg)
    {
        if ($dbQuery->type === 'row_list') {
            return $this->sqlQuery->getRowList($dbQuery->id, $values, $fetchStyle, $fetchArg);
        }

        if ($dbQuery->type === 'row') {
            return $this->sqlQuery->getRow($dbQuery->id, $values, $fetchStyle, $fetchArg);
        }

        $this->sqlQuery->exec($dbQuery->id, $values);

        return [];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function getPager(string $queryId, array $values, Pager $pager): PagesInterface
    {
        $this->logger->start();
        $result = $this->sqlQuery->getPages($queryId, $values, $pager->perPage, $pager->template);
        $this->logger->log($queryId, $values);

        return $result;
    }
}
