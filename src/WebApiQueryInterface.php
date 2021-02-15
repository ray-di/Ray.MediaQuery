<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface WebApiQueryInterface
{
    /**
     * @param array<string, string> $query
     *
     * @return array<string, mixed>
     */
    public function request(string $method, string $uri, array $query): array;
}
