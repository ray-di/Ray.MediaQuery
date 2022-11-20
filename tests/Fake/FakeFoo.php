<?php

namespace Ray\MediaQuery;

use Ray\MediaQuery\Queries\PromiseAddInterface;
use Ray\MediaQuery\Queries\PromiseItemInterface;

class FakeFoo
{
    public function __construct(
        private PromiseAddInterface $promiseAdd,
        private PromiseItemInterface $promiseItem
    ){
    }

    public function add(): void
    {
        $this->promiseAdd->add('1', 'chat');
    }

    public function get(): array
    {
        return $this->promiseItem->get('1');
    }
}
