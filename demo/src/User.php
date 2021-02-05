<?php

declare(strict_types=1);

namespace Demo;

class User
{
    public function __construct(
        private UserAddInterface $userAdd,
        private UserItemInterface $userItem
    ) {
    }

    public function add(string $id, string $title): void
    {
        ($this->userAdd)($id, $title);
    }

    public function get(string $id): array
    {
        return ($this->userItem)($id);
    }
}
