<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Ray\Aop\ReflectiveMethodInvocation;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

class ParamInjectorTest extends TestCase
{
    private ParamInjector $injector;

    protected function setUp(): void
    {
        $this->injector = new ParamInjector(new Injector(new class extends AbstractModule{
            protected function configure(): void
            {
                $this->bind(DateTimeInterface::class)->toInstance(new DateTimeImmutable('1989-9-1'));
            }
        }));
    }

    public function testNoInjection(): void
    {
        $namedArgs = $this->injector->getArgumentes(new ReflectiveMethodInvocation(new FakeParamInjectMethod(), 'noInject', [1]));
        $this->assertSame(['a' => 1], $namedArgs);
    }

    public function testGetArgumentes(): void
    {
        $namedArgs = $this->injector->getArgumentes(new ReflectiveMethodInvocation(new FakeParamInjectMethod(), 'paramInject', []));
        $this->assertInstanceOf(DateTimeImmutable::class, $namedArgs['dateTime']);
    }

    public function testDefaultValue(): void
    {
        $namedArgs = $this->injector->getArgumentes(new ReflectiveMethodInvocation(new FakeParamInjectMethod(), 'defaultValue', []));
        $this->assertSame(['int' => 1, 'bool' => true], $namedArgs);
    }
}
