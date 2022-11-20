<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Ray\MediaQuery\Annotation\Qualifier\UriTemplateBindings;
use Ray\MediaQuery\Exception\WebApiRequestException;

use function json_decode;
use function uri_template;

use const JSON_THROW_ON_ERROR;

final class WebApiQuery implements WebApiQueryInterface
{
    /** @param array<string, string> $uriTemplateBindings */
    public function __construct(
        private ClientInterface $client,
        private MediaQueryLoggerInterface $logger,
        #[UriTemplateBindings] private array $uriTemplateBindings,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function request(string $method, string $uri, array $query): array
    {
        try {
            $this->logger->start();
            $boundUri = uri_template($uri, $this->uriTemplateBindings + $query);
            $response = $this->client->request($method, $boundUri, $query);
            $json = $response->getBody()->getContents();
            /** @var array<string, mixed> $body */
            $body = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $this->logger->log($boundUri, $query);

            return $body;
        } catch (GuzzleException $e) {
            throw new WebApiRequestException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
