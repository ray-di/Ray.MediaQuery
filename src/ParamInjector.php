<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;
use Ray\Aop\MethodInvocation;
use Ray\Di\InjectorInterface;
use Ray\MediaQuery\Exception\CouldNotBeConvertedException;
use ReflectionNamedType;
use ReflectionParameter;
use function count;
use function get_class;
use function is_object;
use function method_exists;

final class ParamInjector implements ParamInjectorInterface
{
    /** @var InjectorInterface */
    private $injector;

    public function __construct(InjectorInterface $injector)
    {
        $this->injector = $injector;
    }

    /**
     * {@inheritDoc}
     */
    public function getArgumentes(MethodInvocation $invocation): array
    {
        $args = (array) $invocation->getArguments();
        $method = $invocation->getMethod();
        $counntArgs = count($args);
        $noInjection = $counntArgs === $method->getNumberOfParameters();
        if ($noInjection) {
            /** @var array<string, mixed> */ // phpcs:ignoreFile
            return (array) $invocation->getNamedArguments();
        }

        $namedArgs = [];
        $parameters = $invocation->getMethod()->getParameters();
        foreach ($parameters as $parameter) {
            $pos = $parameter->getPosition();
            /** @psalm-suppress MixedAssignment */
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
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $parameter->getDefaultValue();
        }

        $object = $this->injector->getInstance($type->getName());
        assert(is_object($object));

        return $object;
    }
}
