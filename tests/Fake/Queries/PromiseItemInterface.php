<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;

interface PromiseItemInterface
{
    /**
     * @DbQuery("promise_item")
     * @return array{id: string, title: string, time: string}
     */
    #[DbQuery('promise_item')]
    public function get(string $id): array;
}
