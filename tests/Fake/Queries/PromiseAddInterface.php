<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use DateTimeInterface;
use Ray\MediaQuery\Annotation\DbQuery;

interface PromiseAddInterface
{
    /**
     * @DbQuery("promise_add")
     */
    #[DbQuery('promise_add')]
    public function add(string $id, string $title, DateTimeInterface $time = null): void;
}
