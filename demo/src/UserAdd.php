<?php

// MediaQueryInterceptor example

declare(strict_types=1);

use Ray\MediaQuery\Annotation\DbQuery;

class UserAdd implements UserAddInterface
{
    #[DbQuery]
    public function __invoke(string $id, string $name): void
    {
    }
}
