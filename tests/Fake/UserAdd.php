<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\MediaQuery\Annotation\QueryId;

final class UserAdd implements UserAddInterface
{
    #[QueryId('user_add')]
    public function __invoke(string $name, int $age): void
    {
    }
}
