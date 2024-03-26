<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Exception\InvalidEntityException;
use Ray\MediaQuery\Factory\TodoEntityFactory;
use Ray\MediaQuery\Factory\TodoInjectionFactory;

class FetchFactoryTest extends TestCase
{
    private FetchFactoryInterface $factory;

    public function setUp(): void
    {
        $this->factory = new FetchFactory('factory');
    }

    public function testFetchClass(): void
    {
        $dbQuery = new DbQuery('todo_item');
        $factory = $this->factory->factory($dbQuery, Todo::class, null);
        $this->assertInstanceOf(FetchClass::class, $factory);
    }

    public function testFetchAssoc(): void
    {
        $dbQuery = new DbQuery('todo_item');
        $factory = $this->factory->factory($dbQuery, null, null);
        $this->assertInstanceOf(FetchAssoc::class, $factory);
    }

    public function testFetchFactory(): void
    {
        $dbQuery = new DbQuery('todo_list', 'row_list', TodoEntityFactory::class);
        $factory = $this->factory->factory($dbQuery, null, null);
        $this->assertInstanceOf(FetchStaticFactory::class, $factory);
    }

    public function testInjectionFactory(): void
    {
        $dbQuery = new DbQuery('todo_list', 'row_list', TodoInjectionFactory::class);
        $factory = $this->factory->factory($dbQuery, null, null);
        $this->assertInstanceOf(FetchInjectionFactory::class, $factory);
    }

    public function testIllegalEntity(): void
    {
        $this->expectException(InvalidEntityException::class);
        $dbQuery = new DbQuery('todo_list', 'row_list');
        $this->factory->factory($dbQuery, '__NOT_EXISTS__', null); // @phpstan-ignore-line
    }

    public function testFetchNewInstance(): void
    {
        $dbQuery = new DbQuery('todo_list', 'row_list');
        $factory = $this->factory->factory($dbQuery, TodoConstruct::class, null);
        $this->assertInstanceOf(FetchNewInstance::class, $factory);
    }
}
