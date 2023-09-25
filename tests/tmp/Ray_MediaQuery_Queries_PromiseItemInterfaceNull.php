<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;

class PromiseItemInterfaceNull implements \Ray\MediaQuery\Queries\PromiseItemInterface
{
    
    #[DbQuery('promise_item', type: 'row')]
    public function get(string $id): array
    {
    }
}