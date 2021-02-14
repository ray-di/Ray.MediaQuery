<?php

namespace Ray\MediaQuery;

class FakeBool implements ToScalarInterface
{
    public function toScalar(): bool
    {
        return true;
    }
}
