<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;

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
}
