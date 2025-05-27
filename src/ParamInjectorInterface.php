<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInvocation;

interface ParamInjectorInterface
{
    /**
     * @param MethodInvocation<T> $invocation
     *
     * @return array<string, mixed>
     *
     * @template T of object
     */
    public function getArguments(MethodInvocation $invocation): array;
}
