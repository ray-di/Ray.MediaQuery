<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\MediaQuery\Exception\InvalidPerPageException;

final class PagesTest extends TestCase
{
    public function testOffsetGetReturnsNullWhenDelegateHasNoPage(): void
    {
        $delegate = $this->createStub(AuraSqlPagerInterface::class);
        $delegate->method('offsetGet')->willReturn(null);

        $pages = new Pages(
            $delegate,
            $this->createStub(ExtendedPdoInterface::class),
            'SELECT 1',
            [],
            10,
        );

        $this->assertFalse(isset($pages[3]));
        $this->assertNull($pages[3]);
    }

    public function testConstructorRejectsZeroPerPage(): void
    {
        $this->expectException(InvalidPerPageException::class);

        new Pages(
            $this->createStub(AuraSqlPagerInterface::class),
            $this->createStub(ExtendedPdoInterface::class),
            'SELECT 1',
            [],
            0,
        );
    }

    public function testConstructorRejectsNegativePerPage(): void
    {
        $this->expectException(InvalidPerPageException::class);

        new Pages(
            $this->createStub(AuraSqlPagerInterface::class),
            $this->createStub(ExtendedPdoInterface::class),
            'SELECT 1',
            [],
            -1,
        );
    }

    public function testGetNbPagesReturnsOneWhenEmpty(): void
    {
        $pdo = $this->createStub(ExtendedPdoInterface::class);
        $pdo->method('fetchValue')->willReturn('0');

        $pages = new Pages(
            $this->createStub(AuraSqlPagerInterface::class),
            $pdo,
            'SELECT * FROM todo',
            [],
            10,
        );

        $this->assertSame(0, $pages->count());
        $this->assertSame(1, $pages->getNbPages());
    }

    public function testCountQueryRunsOnlyOnce(): void
    {
        $pdo = $this->createMock(ExtendedPdoInterface::class);
        $pdo->expects($this->once())->method('fetchValue')->willReturn('3');

        $pages = new Pages(
            $this->createStub(AuraSqlPagerInterface::class),
            $pdo,
            'SELECT * FROM todo',
            [],
            2,
        );

        $this->assertSame(3, $pages->count());
        $this->assertSame(2, $pages->getNbPages());
        $this->assertSame(2, $pages->getNbPages());
    }
}
