<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;
use stdClass;

class CollectionTypeResolverTest extends TestCase
{
    public function testArrayObjectIsCollection(): void
    {
        $this->assertTrue(CollectionTypeResolver::isCollection(ArrayObject::class));
    }

    public function testFakeTodoCollectionIsCollection(): void
    {
        $this->assertTrue(CollectionTypeResolver::isCollection(FakeTodoCollection::class));
    }

    public function testStdClassIsNotCollection(): void
    {
        $this->assertFalse(CollectionTypeResolver::isCollection(stdClass::class));
    }

    public function testGeneratorIsNotCollection(): void
    {
        $this->assertFalse(CollectionTypeResolver::isCollection(Generator::class));
    }

    public function testNonExistentClassIsNotCollection(): void
    {
        $this->assertFalse(CollectionTypeResolver::isCollection('NonExistentClass'));
    }

    public function testPagesIsNotCollection(): void
    {
        $this->assertFalse(CollectionTypeResolver::isCollection(Pages::class));
    }

    public function testZeroArgConstructorIsNotCollection(): void
    {
        // SplObjectStorage implements Iterator but has no constructor params
        $this->assertFalse(CollectionTypeResolver::isCollection(SplObjectStorage::class));
    }
}
