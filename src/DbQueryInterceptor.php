<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

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
use function is_int;
use function is_string;

class DbQueryInterceptor implements MethodInterceptor
{
    public function __construct(
        private SqlQueryInterface         $sqlQuery,
        private ParamInjectorInterface    $paramInjector,
        private ReturnEntityInterface     $returnEntity,
        private FetchFactoryInterface     $factory,
        private DbPager                   $pager,
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
            return ($this->pager)($dbQuery->id, $values, $pager, $entity);
        }

        /** @var ReflectionNamedType|ReflectionUnionType|null $returnType */
        $returnType = $invocation->getMethod()->getReturnType();
        $fetch = $this->factory->factory($dbQuery, $entity, $returnType);
        $isRow = $dbQuery->type === 'row' || $returnType instanceof ReflectionUnionType || ($returnType instanceof ReflectionNamedType && $returnType->getName() !== 'array');

        return $isRow ? $this->sqlQuery->getRow($dbQuery->id, $values, $fetch) : $this->sqlQuery->getRowList($dbQuery->id, $values, $fetch);
    }
}
