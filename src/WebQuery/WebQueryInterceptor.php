<?php

declare(strict_types=1);

namespace Ray\MediaQuery\WebQuery;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\MediaQuery\Annotation\Qualifier\WebApiList;
use Ray\MediaQuery\Annotation\WebQuery;
use Ray\MediaQuery\DbQuery\Pages;
use Ray\MediaQuery\ParamInjectorInterface;

class WebQueryInterceptor implements MethodInterceptor
{
    /** @var WebApiQueryInterface */
    private $webApiQuery;

    /** @var ParamInjectorInterface  */
    private $paramInjector;

    /** @var array<string, array{method: string, path: string}> */
    private $webApiList;

    /**
     * @param array<string, array{method: string, path: string}> $webApiList
     *
     * @WebApiList("webApiList")
     */
    #[WebApiList('webApiList')]
    public function __construct(WebApiQueryInterface $webApiQuery, ParamInjectorInterface $paramInjector, array $webApiList)
    {
        $this->webApiQuery = $webApiQuery;
        $this->paramInjector = $paramInjector;
        $this->webApiList = $webApiList;
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
        $request = $this->webApiList[$webQuery->id];

        return $this->webApiQuery->request($request['method'], $request['path'], $values);
    }
}
