<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Named;
use Ray\MediaQuery\Annotation\WebQuery;

class WebQueryInterceptor implements MethodInterceptor
{
    /** @var WebApiQueryInterface */
    private $webApiQuery;

    /** @var ParamInjectorInterface  */
    private $paramInjector;

    /** @var array<string, array{method: string, path: string}> */
    private $mediaQueryConfig;

    /**
     * @param array<string, array{method: string, path: string}> $mediaQueryConfig
     *
     * @Named("mediaQueryConfig=media_query_config")
     */
    #[Named('mediaQueryConfig=media_query_config')]
    public function __construct(WebApiQueryInterface $webApiQuery, ParamInjectorInterface $paramInjector, array $mediaQueryConfig)
    {
        $this->webApiQuery = $webApiQuery;
        $this->paramInjector = $paramInjector;
        $this->mediaQueryConfig = $mediaQueryConfig;
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
        $request = $this->mediaQueryConfig[$webQuery->id];

        return $this->webApiQuery->request($request['method'], $request['path'], $values);
    }
}
