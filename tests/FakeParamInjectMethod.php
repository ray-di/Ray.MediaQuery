<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;

class FakeParamInjectMethod
{
    public function noInject(int $a): void
    {
    }

    public function paramInject(DateTimeInterface|null $dateTime = null): void
    {
    }

    public function paramConvert(FakeBool|null $bool = null, FakeString|null $string = null, DateTimeInterface|null $dateTime = null): void
    {
    }

    public function defaultValue(int $int = 1, bool $bool = true): void
    {
    }
}
