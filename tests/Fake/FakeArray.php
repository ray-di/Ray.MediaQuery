<?php

namespace Ray\MediaQuery;

class FakeArray implements ToScalarInterface
{
    /** @return array<int> */
    public function toScalar(): array
    {
        return [0, 1];
    }
}
