<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;
use Ray\Aop\MethodInvocation;
use Ray\Di\InjectorInterface;
use ReflectionNamedType;
use ReflectionParameter;

use function count;

final class ParamInjector implements ParamInjectorInterface
{
    /** @var InjectorInterface */
    private $injector;

    public function __construct(InjectorInterface $injector)
    {
        $this->injector = $injector;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArgumentes(MethodInvocation $invocation): array
    {
        $args = (array) $invocation->getArguments();
        $method = $invocation->getMethod();
        $counntArgs = count($args);
        $noInjection = $counntArgs === $method->getNumberOfParameters();
        if ($noInjection) {
            return (array) $invocation->getNamedArguments();
        }

        $namedArgs = [];
        $parameters = $invocation->getMethod()->getParameters();
        foreach ($parameters as $parameter) {
            $pos = $parameter->getPosition();
            $namedArgs[$parameter->getName()] = $args[$pos] ?? $this->getInjectedParam($parameter);
        }

        return $namedArgs;
    }

    /**
     * @return mixed
     */
    private function getInjectedParam(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType) {
            return $parameter->getDefaultValue();
        }

        $instance = $this->injector->getInstance($type->getName());

        return $instance instanceof DateTimeInterface ? $instance : (string) $instance;
    }
}
