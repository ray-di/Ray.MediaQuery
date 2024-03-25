<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\MediaQuery\Annotation\Qualifier\WebApiList;
use Ray\MediaQuery\Annotation\WebQuery;

final class WebQueryInterceptor implements MethodInterceptor
{
    /** @param array<string, array{method: string, path: string}> $webApiList */
    public function __construct(
        private WebApiQueryInterface $webApiQuery,
        private ParamInjectorInterface $paramInjector,
        #[WebApiList]
        private array $webApiList,
    ) {
    }

    /** @return Pages<mixed>|array<string, mixed> */
    public function invoke(MethodInvocation $invocation): Pages|array
    {
        $method = $invocation->getMethod();
        /** @var WebQuery $webQuery */
        $webQuery = $method->getAnnotation(WebQuery::class);
        /** @var array<string, string> $values */
        $values = $this->paramInjector->getArgumentes($invocation);
        $request = $this->webApiList[$webQuery->id];

        return $this->webApiQuery->request($request['method'], $request['path'], $values);
    }
}
