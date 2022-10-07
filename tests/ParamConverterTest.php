<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Exception\CouldNotBeConvertedException;
use stdClass;

class ParamConverterTest extends TestCase
{
    public function testInvoke(): void
    {
        $values = ['date_val' => new UnixEpocTime(), 'bool_val' => new FakeBool(), 'string_val' => new FakeString()];
        (new ParamConverter())($values);
        $this->assertSame(['date_val' => UnixEpocTime::TEXT, 'bool_val' => true, 'string_val' => 'a'], $values);
    }

    public function testInvalidParam(): void
    {
        $this->expectException(CouldNotBeConvertedException::class);
        $values = ['invalid' => new stdClass()];
        (new ParamConverter())($values);
    }
}
