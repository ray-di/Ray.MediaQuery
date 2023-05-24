<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Set;
use Ray\Di\ProviderInterface;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use ReflectionNamedType;
use ReflectionUnionType;

class DbQueryInterceptor implements MethodInterceptor
{
    public function __construct(
        private SqlQueryInterface $sqlQuery,
        private ParamInjectorInterface $paramInjector,
        private ReturnEntityInterface $returnEntity,
        private FetchFactoryInterface $factory,
        #[Set(DbPager::class)] private ProviderInterface $dbPagerProvider,
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
            $dbPager = $this->dbPagerProvider->get();

            return ($dbPager)($dbQuery->id, $values, $pager, $entity);
        }

        /** @var ReflectionNamedType|ReflectionUnionType|null $returnType */
        $returnType = $invocation->getMethod()->getReturnType();
        $fetch = $this->factory->factory($dbQuery, $entity, $returnType);
        $isRow = $dbQuery->type === 'row' || $returnType instanceof ReflectionUnionType || ($returnType instanceof ReflectionNamedType && $returnType->getName() !== 'array');

        return $isRow ? $this->sqlQuery->getRow($dbQuery->id, $values, $fetch) : $this->sqlQuery->getRowList($dbQuery->id, $values, $fetch);
    }
}
