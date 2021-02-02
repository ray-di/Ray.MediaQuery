<?php

// SqlQuery example

use Ray\MediaQuery\SqlQueryInterface;

class UserItem implements UserItemInterface
{
    public function __construct(
        private SqlQueryInterface $sqlQuery
    ){}

    public function __invoke(string $id): array
    {
        return $this->sqlQuery->getRow('user_item', ['id' => $id]);
    }
}
