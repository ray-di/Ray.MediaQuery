<?php

declare(strict_types=1);

namespace Demo;

use Ray\MediaQuery\Annotation\DbQuery;

interface UserAddInterface
{
    #[DbQuery('user_add')]
    public function __invoke(string $id, string $name): void;
}
