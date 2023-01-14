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
}
