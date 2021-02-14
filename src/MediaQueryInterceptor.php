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

    /** @var InjectorInterface */
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
    public function getPage(string $queryId, array $values, Pager $pager): Pages
    {
        return $this->sqlQuery->getPages($queryId, $values, $pager->perPage, $pager->template);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function getPager(string $queryId, array $values, Pager $pager): Pages
    {
        $this->logger->start();
        $result = $this->getPage($queryId, $values, $pager);
        $this->logger->log($queryId, $values);

        return $result;
    }
}
