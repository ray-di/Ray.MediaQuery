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
use Ray\MediaQuery\Result\PostQueryInterface;
use ReflectionNamedType;
use ReflectionUnionType;

use function assert;
use function class_exists;
use function is_subclass_of;

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

    /** @return array<mixed>|object|null */
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
        if ($returnType instanceof ReflectionNamedType) {
            $typeName = $returnType->getName();
            if (class_exists($typeName) && is_subclass_of($typeName, PostQueryInterface::class)) {
                return $this->sqlQuery->execPostQuery($dbQuery->id, $values, $typeName);
            }
        }

        $fetch = $this->factory->factory($dbQuery, $entity, $returnType);
        $isRow = $dbQuery->type === 'row' || $returnType instanceof ReflectionUnionType || ($returnType instanceof ReflectionNamedType && $returnType->getName() !== 'array');

        return $isRow ? $this->sqlQuery->getRow($dbQuery->id, $values, $fetch) : $this->sqlQuery->getRowList($dbQuery->id, $values, $fetch);
    }
}
