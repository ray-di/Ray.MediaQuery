<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;

use function substr;

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
     * @return Pages|array<mixed>
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

        return $this->sqlQuery($dbQury->id, $values);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<mixed>
     */
    private function sqlQuery(string $queryId, array $values): array
    {
        $postFix = substr($queryId, -4);
        if ($postFix === 'list') {
            return $this->sqlQuery->getRowList($queryId, $values);
        }

        if ($postFix === 'item') {
            return $this->sqlQuery->getRow($queryId, $values);
        }

        $this->sqlQuery->exec($queryId, $values);

        return [];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function getPager(string $queryId, array $values, Pager $pager): Pages
    {
        $this->logger->start();
        $result = $this->sqlQuery->getPages($queryId, $values, $pager->perPage, $pager->template);
        $this->logger->log($queryId, $values);

        return $result;
    }
}
