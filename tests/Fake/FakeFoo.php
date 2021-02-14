<?php

namespace Ray\MediaQuery;

use Ray\MediaQuery\Queries\PromiseAddInterface;
use Ray\MediaQuery\Queries\PromiseItemInterface;

class FakeFoo
{
    /** @var PromiseAddInterface */
    private PromiseAddInterface $promiseAdd;

    /** @var PromiseItemInterface  */
    private PromiseItemInterface $promiseItem;

    public function __construct(PromiseAddInterface $promiseAdd, PromiseItemInterface $promiseItem)
    {
        $this->promiseAdd = $promiseAdd;
        $this->promiseItem = $promiseItem;
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