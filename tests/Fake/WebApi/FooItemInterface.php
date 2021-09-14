<?php

declare(strict_types=1);

namespace Ray\MediaQuery\WebApi;

use Ray\MediaQuery\Annotation\WebQuery;

interface FooItemInterface
{
    /**
     * @WebQuery("foo_item")
     */
    #[WebQuery('foo_item')]
    public function __invoke(string $id): array;
}
