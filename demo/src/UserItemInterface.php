<?php

declare(strict_types=1);

interface UserItemInterface
{
    /**
     * @return array{id: string, name: string}
     */
    public function __invoke(string $id): array;
}
