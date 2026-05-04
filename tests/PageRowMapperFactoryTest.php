<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\InjectorInterface;
use Ray\Di\Name;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Factory\TodoInjectionFactory;

final class PageRowMapperFactoryTest extends TestCase
{
    public function testInjectedFactoryIsResolvedLazilyAndReused(): void
    {
        $injector = new class (new class {
            public function factory(string $id, string $title): string
            {
                return $id . ':' . $title;
            }
        }) implements InjectorInterface {
            public int $instances = 0;

            public function __construct(
                private object $instance,
            ) {
            }

            public function getInstance($interface, $name = Name::ANY): object
            {
                unset($interface, $name);
                $this->instances++;

                return $this->instance;
            }
        };
        $factory = new PageRowMapperFactory('factory', $injector);
        $rowMapper = $factory->create(new DbQuery('todo_list', factory: TodoInjectionFactory::class));

        $this->assertIsCallable($rowMapper);
        $this->assertSame(0, $injector->instances);

        $this->assertSame('1:run', $rowMapper(['1', 'run']));
        $this->assertSame(1, $injector->instances);

        $this->assertSame('2:walk', $rowMapper(['2', 'walk']));
        $this->assertSame(1, $injector->instances);
    }
}
