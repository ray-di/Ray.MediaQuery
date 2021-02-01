<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;

class MediaQueryTest extends TestCase
{
    /** @var MediaQuery */
    protected $mediaQuery;

    protected function setUp(): void
    {
        $this->mediaQuery = new MediaQuery();
    }

    public function testIsInstanceOfMediaQuery(): void
    {
        $actual = $this->mediaQuery;
        $this->assertInstanceOf(MediaQuery::class, $actual);
    }
}
