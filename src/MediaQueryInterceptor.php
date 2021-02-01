<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Named;
use Ray\Di\InjectorInterface;
use Ray\MediaQuery\Annotation\QueryId;
use Ray\MediaQuery\Annotation\SqlDir;

use function assert;
use function file_exists;
use function sprintf;

class MediaQueryInterceptor implements MethodInterceptor
{
    /** @var string */
    private $sqlDir;

    /** @var SqlQueryInterface */
    private $sqlQuery;
    private MediaQueryLoggerInterface $logger;

    #[Named('sqlDir=Ray\MediaQuery\Annotation\SqlDir')]
    public function __construct(string $sqlDir, SqlQueryInterface $sqlQuery, MediaQueryLoggerInterface $logger)
    {
        $this->sqlDir = $sqlDir;
        $this->sqlQuery = $sqlQuery;
        $this->logger = $logger;
    }

    public function invoke(MethodInvocation $invocation)
    {
        $queryId = $invocation->getMethod()->getAnnotation(QueryId::class);
        assert($queryId instanceof QueryId);
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $queryId->id);
        if (file_exists($sqlFile)) {
            $params = (array) $invocation->getNamedArguments();
            $result = ($this->sqlQuery)($sqlFile, $params);
            $this->logger->log($queryId, $params);

            return $result;
        }
    }
}
