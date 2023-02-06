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
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

use function assert;
use function is_callable;
use function is_int;
use function is_string;
use function method_exists;

class DbQueryInterceptor implements MethodInterceptor
{
    public function __construct(
        private SqlQueryInterface $sqlQuery,
        private MediaQueryLoggerInterface $logger,
        private ParamInjectorInterface $paramInjector,
        private ReturnEntityInterface $returnEntity,
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
        $entity = ($this->returnEntity)($method);
        if ($pager instanceof Pager) {
            return $this->getPager($dbQuery->id, $values, $pager, $entity);
        }

        /** @var ReflectionNamedType|null $returnType */
        $returnType = $invocation->getMethod()->getReturnType();

        $maybeFactory = [$dbQuery->factory, 'factory'];
        if (is_callable($maybeFactory)) {
            return $this->sqlQuery($returnType, $dbQuery, $values, PDO::FETCH_FUNC, $maybeFactory);
        }

        $fetchStyle = $this->getFetchMode($entity);

        return $this->sqlQuery($returnType, $dbQuery, $values, $fetchStyle, (string) $entity);
    }

    /**
     * @param ?class-string $entity
     *
     * @return PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchStyle
     */
    private function getFetchMode(string|null $entity): int
    {
        if ($entity === null) {
            return PDO::FETCH_ASSOC;
        }

        return method_exists($entity, '__construct') ? PDO::FETCH_FUNC : PDO::FETCH_CLASS;
    }

    /**
     * @param array<string, mixed>                              $values
     * @param PDO::FETCH_ASSOC|PDO::FETCH_CLASS|PDO::FETCH_FUNC $fetchStyle
     *
     * @return array<mixed>|object|null
     */
    private function sqlQuery(ReflectionType|null $returnType, DbQuery $dbQuery, array $values, int $fetchStyle, int|string|callable $fetchArg): array|object|null
    {
        if ($dbQuery->type === 'row' || $returnType instanceof ReflectionUnionType || ($returnType instanceof ReflectionNamedType && $returnType->getName() !== 'array')) {
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
