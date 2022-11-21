<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInvocation;

interface ParamInjectorInterface
{
    /** @return array<string, mixed> */
    public function getArgumentes(MethodInvocation $invocation): array;
}
