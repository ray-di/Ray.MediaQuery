<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use phpDocumentor\Reflection\DocBlockFactory;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Entity\FakeEntity;
use ReflectionMethod;

class ReturnTypeTest extends TestCase
{
    private ReturnEntity $returnEntity;

    protected function setUp(): void
    {
        $this->returnEntity = new ReturnEntity(DocBlockFactory::createInstance());
    }

    public function testReturnItem(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'item');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(FakeEntity::class, $entity);
    }

    public function testReturnList(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'list');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(FakeEntity::class, $entity);
    }

    public function testNoReturnType(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'noReturn');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(null, $entity);
    }

    public function testnoPhpDoc(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'noPhpDoc');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(null, $entity);
    }

    public function testNoPhpDocFakePages(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'noPhpDocFakePages');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(null, $entity);
    }

    public function testNoReturnDoc(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'noReturnDoc');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(null, $entity);
    }

    public function testNoReturnDocFakePages(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'noReturnDocFakePages');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(null, $entity);
    }

    public function testNonEntityGeneric(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'nonEntityGeneric');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(null, $entity);
    }

    public function testInvalidReturnType(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'invalidReturnType');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(null, $entity);
    }

    public function testReturnArray(): void
    {
        $method = new ReflectionMethod(new FakeReturn(), 'returnArray');
        $entity = ($this->returnEntity)($method);

        $this->assertSame(null, $entity);
    }
}
