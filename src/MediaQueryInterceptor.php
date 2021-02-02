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
use function ltrim;
use function preg_replace;
use function sprintf;
use function strrpos;
use function strtolower;
use function substr;

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

    public function invoke(MethodInvocation $invocation): array
    {
        $queryId = $this->getQueryId($invocation);
        $sqlFile = sprintf('%s/%s.sql', $this->sqlDir, $queryId);
        if (file_exists($sqlFile)) {
            $params = (array) $invocation->getNamedArguments();
            $result = $this->sqlQuery($queryId, $params);
            $this->logger->log($queryId, $params);

            return $result;
        }
    }

    /**
     * @param array<string, string> $params
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

    private function getQueryId(MethodInvocation $invocation): string
    {
        $fullName = $invocation->getMethod()->getDeclaringClass()->getName();
        $name = substr($fullName, strrpos($fullName, '\\') + 1);

        // @see https://qiita.com/okapon_pon/items/498b88c2f91d7c42e9e8
        return ltrim(strtolower(preg_replace(/** @lang regex */'/[A-Z]/', /** @lang regex */'_\0', $name)), '_');
    }
}
