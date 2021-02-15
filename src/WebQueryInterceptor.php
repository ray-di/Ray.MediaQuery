<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\MediaQuery\Annotation\WebQuery;

class WebQueryInterceptor implements MethodInterceptor
{
    /** @var WebApiQueryInterface */
    private $webApiQuery;

    /** @var MediaQueryLoggerInterface */
    private $logger;

    /** @var ParamInjectorInterface  */
    private $paramInjector;

    public function __construct(WebApiQueryInterface $webApiQuery, MediaQueryLoggerInterface $logger, ParamInjectorInterface $paramInjector)
    {
        $this->webApiQuery = $webApiQuery;
        $this->logger = $logger;
        $this->paramInjector = $paramInjector;
    }

    /**
     * @return Pages|array<mixed>
     */
    public function invoke(MethodInvocation $invocation)
    {
        $method = $invocation->getMethod();
        /** @var WebQuery $webQuery */
        $webQuery = $method->getAnnotation(WebQuery::class);
        /** @var array<string, string> $values */
        $values = $this->paramInjector->getArgumentes($invocation);

        return $this->webApiQuery->request($webQuery->method, $webQuery->uri, $values);
    }
}
