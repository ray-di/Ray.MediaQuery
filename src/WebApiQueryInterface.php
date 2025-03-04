<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Psr\Http\Message\MessageInterface;

interface WebApiQueryInterface
{
    /**
     * @param array<string, string> $query
     *
     * @return array<string, mixed>
     */
    public function request(string $method, string $uri, array $query): array;

    /**
     * Returns the response body as a string
     *
     * @param array<string, string> $query
     */
    public function getStringBody(string $method, string $uri, array $query): string;

    /**
     * Returns the raw PSR-7 HTTP Message
     *
     * @param array<string, string> $query
     */
    public function getHttpMessage(string $method, string $uri, array $query): MessageInterface;
}
