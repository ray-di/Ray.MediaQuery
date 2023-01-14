<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Entity\FakeEntity;
use ReflectionMethod;

class ReturnTypeTest extends TestCase
{
    public function testReturnItem(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'item');
        $entity = new ReturnEntity($method);

        $this->assertSame(FakeEntity::class, $entity->type);
    }

    public function testReturnList(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'list');
        $entity = new ReturnEntity($method);

        $this->assertSame(FakeEntity::class, $entity->type);
    }

    public function testNoReturnType(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'noReturn');
        $entity = new ReturnEntity($method);

        $this->assertSame(null, $entity->type);
    }

    public function testnoPhpDoc(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'noPhpDoc');
        $entity = new ReturnEntity($method);

        $this->assertSame(null, $entity->type);
    }

    public function testNoReturnDoc(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'noReturnDoc');
        $entity = new ReturnEntity($method);

        $this->assertSame(null, $entity->type);
    }

    public function testNonEntityGeneric(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'nonEntityGeneric');
        $entity = new ReturnEntity($method);

        $this->assertSame(null, $entity->type);
    }

    public function testInvalidReturnType(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'invalidReturnType');
        $entity = new ReturnEntity($method);

        $this->assertSame(null, $entity->type);
    }

    public function testReturnArray(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'returnArray');
        $entity = new ReturnEntity($method);

        $this->assertSame(null, $entity->type);
    }
}
