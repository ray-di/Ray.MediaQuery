<?php

namespace Ray\MediaQuery;

use DateTime;
use PHPUnit\Framework\TestCase;

class ParamConverterTest extends TestCase
{

    public function testInvoke()
    {
        $values = ['date_val' => new UnixEpocTime(), 'bool_val'=> new FakeBool(), 'string_val'=> new FakeString()];
        (new ParamConverter())($values);
        $this->assertSame(['date_val' => UnixEpocTime::TEXT, 'bool_val' => true, 'string_val' => 'a'], $values);
    }
}
