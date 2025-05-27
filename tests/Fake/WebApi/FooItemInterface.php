<?php

declare(strict_types=1);

namespace Ray\MediaQuery\WebApi;

use Psr\Http\Message\MessageInterface;
use Ray\MediaQuery\Annotation\WebQuery;

interface FooItemInterface
{
    #[WebQuery('foo_item')]
    public function item(string $id): array;

    #[WebQuery('foo_item')]
    public function body(string $id): string;

    #[WebQuery('foo_item')]
    public function message(string $id): MessageInterface;

    #[WebQuery('foo_item')]
    public function boolean(string $id): bool;
}
