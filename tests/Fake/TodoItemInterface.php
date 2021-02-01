<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface TodoItemInterface
{
    /**
     * @return array{id: string, title: string}
     */
    public function __invoke(string $id): array;
}
