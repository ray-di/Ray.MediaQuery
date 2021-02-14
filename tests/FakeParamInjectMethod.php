<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;

class FakeParamInjectMethod
{
    public function paramInject(?FakeBool $bool = null, ?FakeString $string = null, ?DateTimeInterface $dateTime = null): void
    {
    }
}
