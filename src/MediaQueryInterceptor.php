<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use LogicException;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Named;
use Ray\Di\InjectorInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;

use function file_exists;
use function sprintf;
use function substr;

class MediaQueryInterceptor implements MethodInterceptor
{
    /** @var string */
    private $sqlDir;

    /** @var SqlQueryInterface */
    private $sqlQuery;

    /** @var MediaQueryLoggerInterface */
    private $logger;
    private InjectorInterface $injector;

    /** @var ParamInjectorInterface  */
    private $paramInjector;

    /**
     * @Named("sqlDir=Ray\MediaQuery\Annotation\SqlDir")
     */
    #[Named('sqlDir=Ray\MediaQuery\Annotation\SqlDir')]
    public function __construct(string $sqlDir, SqlQueryInterface $sqlQuery, MediaQueryLoggerInterface $logger, InjectorInterface $injector, ParamInjectorInterface $paramInjector)
    {
        $this->sqlDir = $sqlDir;
        $this->sqlQuery = $sqlQuery;
        $this->logger = $logger;
        $this->injector = $injector;
        $this->paramInjector = $paramInjector;
    }

    /**
     * @return Pages|array<mixed>
     */
    public function invoke(MethodInvocation $invocation)
    {
        /** @var DbQuery $dbQury */
        $dbQury = $invocation->getMethod()->getAnnotation(DbQuery::class);
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $dbQury->id);
        if (! file_exists($sqlFile)) {
            throw new LogicException($sqlFile);
        }

        $pager = $invocation->getMethod()->getAnnotation(Pager::class);
        /** @var array<string, mixed> $params */
        $params = $this->paramInjector->getArgumentes($invocation);
        if ($pager instanceof Pager) {
            return $this->getPager($dbQury->id, $params, $pager);
        }

        return $this->sqlQuery($dbQury->id, $params);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<mixed>
     */
    private function sqlQuery(string $queryId, array $params): array
    {
        $postFix = substr($queryId, -4);
        if ($postFix === 'list') {
            return $this->sqlQuery->getRowList($queryId, $params);
        }

        if ($postFix === 'item') {
            return $this->sqlQuery->getRow($queryId, $params);
        }

        $this->sqlQuery->exec($queryId, $params);

        return [];
    }

    /**
     * @param array<string, mixed> $params
     */
    private function getPage(string $queryId, array $params, Pager $pager): Pages
    {
        return $this->sqlQuery->getPages($queryId, $params, $pager->perPage, $pager->template);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function getPager(string $queryId, array $params, Pager $pager): Pages
    {
        $this->logger->start();
        $result = $this->getPage($queryId, $params, $pager);
        $this->logger->log($queryId, $params);

        return $result;
    }
}
