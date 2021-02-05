<?php

declare(strict_types=1);

namespace Demo;

use Ray\MediaQuery\Annotation\DbQuery;

interface UserItemInterface
{
    /**
     * @return array{id: string, name: string}
     */
    #[DbQuery('user_item')]
    public function __invoke(string $id): array;
}
