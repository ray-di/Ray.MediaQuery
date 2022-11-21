<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;

interface PromiseListInterface
{
    #[DbQuery('promise_list')]
    public function get(): array;
}
