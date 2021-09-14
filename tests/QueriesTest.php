<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\FromDir\TodoAddInterface;
use Ray\MediaQuery\FromDir\TodoItemInterface;

class QueriesTest extends TestCase
{
    public function testFromClasses(): void
    {
        $classes = [TodoAddInterface::class, TodoItemInterface::class];
        $mediaQueries = Queries::fromClasses($classes);
        $this->assertSame($classes, $mediaQueries->classes);
    }

    public function testFromDir(): void
    {
        $mediaQueries = Queries::fromDir(__DIR__ . '/Fake/FromDir');
        $this->assertSame([
            TodoAddInterface::class,
            TodoItemInterface::class,
        ], $mediaQueries->classes);
    }

    public function testFromDirCache(): void
    {
        $mediaQueries = Queries::fromDir(__DIR__ . '/Fake/FromDir');
        $this->assertSame([
            TodoAddInterface::class,
            TodoItemInterface::class,
        ], $mediaQueries->classes);
    }
}
