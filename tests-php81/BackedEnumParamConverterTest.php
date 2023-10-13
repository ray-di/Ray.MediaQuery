<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;

class BackedEnumParamConverterTest extends TestCase
{
    public function testInvoke(): void
    {
        $values = ['status' => FakeBackedEnum::Public];
        (new ParamConverter())($values);
        $this->assertSame(['status' => 'public'], $values);
    }
}
