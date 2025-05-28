<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Aop\MethodInvocation;
use Ray\Di\InjectorInterface;
use ReflectionNamedType;
use ReflectionParameter;

use function count;

final class ParamInjector implements ParamInjectorInterface
{
    public function __construct(
        private InjectorInterface $injector
    ){
    }

    /**
     * @template T of object
     * @param MethodInvocation<T> $invocation
     * @return array<string, mixed>
     */
    #[\Override]
    public function getArguments(MethodInvocation $invocation): array
    {
        $args = (array) $invocation->getArguments();
        $method = $invocation->getMethod();
        $countArgs = count($args);
        $noInjection = $countArgs === $method->getNumberOfParameters();
        if ($noInjection) {
            /** @var array<string, mixed> */ // phpcs:ignoreFile
            return (array) $invocation->getNamedArguments();
        }

        $namedArgs = [];
        $parameters = $invocation->getMethod()->getParameters();
        foreach ($parameters as $parameter) {
            $pos = $parameter->getPosition();
            /** @psalm-suppress MixedAssignment */
            $namedArgs[$parameter->getName()] = array_key_exists($pos, $args)
                ? $args[$pos] : $this->getInjectedParam($parameter);
        }

        return $namedArgs;
    }

    /**
     * @return mixed
     */
    private function getInjectedParam(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $parameter->getDefaultValue();
        }

        $object = $this->injector->getInstance($type = $type->getName()); // @phpstan-ignore-line

        return $object;
    }
}
