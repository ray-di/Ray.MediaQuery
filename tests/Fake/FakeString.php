<?php

namespace Ray\MediaQuery;

class FakeString implements \Stringable
{
    public function __toString(): string
    {
        return 'a';
    }
}
