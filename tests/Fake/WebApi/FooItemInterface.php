<?php

declare(strict_types=1);

namespace Ray\MediaQuery\WebApi;

use Ray\MediaQuery\Annotation\WebQuery;

interface FooItemInterface
{
    #[WebQuery(method: 'GET', uri: 'https://{domain}/anything/{id}')]
    public function __invoke(string $id): array;
}
