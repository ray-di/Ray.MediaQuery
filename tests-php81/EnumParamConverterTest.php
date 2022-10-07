<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;

class EnumParamConverterTest extends TestCase
{
    public function testInvoke(): void
    {
        $values = ['status' => FakeEnum::public];
        (new ParamConverter())($values);
        $this->assertSame(['status' => 'public'], $values);
    }
}
