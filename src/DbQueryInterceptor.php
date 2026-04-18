<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Set;
use Ray\Di\ProviderInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Result\AffectedRows;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;

use function assert;

final class DbQueryInterceptor implements MethodInterceptor
{
    /** @param ProviderInterface<DbPager> $dbPagerProvider */
    public function __construct(
        private SqlQueryInterface $sqlQuery,
        private ParamInjectorInterface $paramInjector,
        private ReturnEntityInterface $returnEntity,
        private FetchFactoryInterface $factory,
        #[Set(DbPager::class)]
        private ProviderInterface $dbPagerProvider,
    ) {
    }

    /** @return array<mixed>|object|AffectedRows|null */
    #[Override]
    public function invoke(MethodInvocation $invocation): array|object|null
    {
        $method = $invocation->getMethod();
        $dbQuery = $method->getAnnotation(DbQuery::class);
        assert($dbQuery instanceof DbQuery);
        $pager = $method->getAnnotation(Pager::class);
        $values = $this->paramInjector->getArguments($invocation);
        $entity = ($this->returnEntity)($method);
        if ($pager instanceof Pager) {
            $dbPager = $this->dbPagerProvider->get();

            return ($dbPager)($dbQuery->id, $values, $pager, $entity);
        }

        $returnType = $invocation->getMethod()->getReturnType();
        assert($returnType === null || $returnType instanceof ReflectionNamedType || $returnType instanceof ReflectionUnionType);
        if ($returnType instanceof ReflectionNamedType && $returnType->getName() === AffectedRows::class) {
            return $this->sqlQuery->execAffected($dbQuery->id, $values);
        }

        if ($returnType instanceof ReflectionNamedType && CollectionTypeResolver::isCollection($returnType->getName())) {
            $fetch = $this->factory->factory($dbQuery, $entity, $returnType);
            /** @var array<mixed> $rows */
            $rows = $this->sqlQuery->getRowList($dbQuery->id, $values, $fetch);
            /** @var class-string $class */
            $class = $returnType->getName();

            return (new ReflectionClass($class))->newInstanceArgs([$rows]);
        }

        $fetch = $this->factory->factory($dbQuery, $entity, $returnType);
        $isRow = $dbQuery->type === 'row' || $returnType instanceof ReflectionUnionType || ($returnType instanceof ReflectionNamedType && $returnType->getName() !== 'array');

        return $isRow ? $this->sqlQuery->getRow($dbQuery->id, $values, $fetch) : $this->sqlQuery->getRowList($dbQuery->id, $values, $fetch);
    }
}
