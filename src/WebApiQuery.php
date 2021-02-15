<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

use function json_decode;
use function uri_template;

final class WebApiQuery implements WebApiQueryInterface
{
    /** @var ClientInterface */
    private $client;

    /**
     * @var MediaQueryLoggerInterface
     */
    private MediaQueryLoggerInterface $logger;

    public function __construct(ClientInterface $client, MediaQueryLoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function request(string $method, string $uri, array $query): array
    {
        try {
            $this->logger->start();
            $boundUri = uri_template($uri, $query);
            $response = $this->client->request($method, $boundUri, $query);
            $json = $response->getBody()->getContents();
            /** @var array<string, mixed> $body */
            $body = json_decode($json, true);
            $this->logger->log($boundUri, $query);
            return $body;
        } catch (GuzzleException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }
}
