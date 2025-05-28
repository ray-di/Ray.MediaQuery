<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use Psr\Http\Message\MessageInterface;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\MediaQuery\Annotation\Qualifier\WebApiList;
use Ray\MediaQuery\Annotation\WebQuery;
use Ray\MediaQuery\Exception\NotSupportedReturnTypeException;
use ReflectionNamedType;

use function is_a;

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

    /** @return array<string, mixed>|string|MessageInterface */
    #[Override]
    public function invoke(MethodInvocation $invocation): array|string|MessageInterface
    {
        $method = $invocation->getMethod();
        /** @var WebQuery $webQuery */
        $webQuery = $method->getAnnotation(WebQuery::class);
        /** @var array<string, string> $values */
        $values = $this->paramInjector->getArguments($invocation);
        $request = $this->webApiList[$webQuery->id];

        $returnType = $method->getReturnType();
        if (
            $returnType instanceof ReflectionNamedType &&
            is_a($returnType->getName(), MessageInterface::class, true)
        ) {
            return $this->webApiQuery->getHttpMessage($request['method'], $request['path'], $values);
        }

        if ($returnType instanceof ReflectionNamedType && $returnType->getName() === 'string') {
            return $this->webApiQuery->getStringBody($request['method'], $request['path'], $values);
        }

        if ($returnType instanceof ReflectionNamedType && $returnType->getName() === 'array') {
            return $this->webApiQuery->request($request['method'], $request['path'], $values);
        }

        throw new NotSupportedReturnTypeException();
    }
}
