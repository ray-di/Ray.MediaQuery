<?php

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;

class GetClassNameTest extends TestCase
{
    public function testNoLoad()
    {
        $className = (new GetClassName())(__FILE__);
        $this->assertSame('', $className);
    }
}
