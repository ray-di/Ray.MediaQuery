<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;

class FakeParamInjectMethod
{
    public function paramInject(?DateTimeInterface $dateTime = null): void
    {
    }

    public function paramConvert(?FakeBool $bool = null, ?FakeString $string = null, ?DateTimeInterface $dateTime = null): void
    {
    }

    public function defaultValue(int $int = 1, bool $bool = true): void
    {
    }
}
