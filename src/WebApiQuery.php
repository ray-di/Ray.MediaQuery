<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Ray\MediaQuery\Annotation\Qualifier\UriTemplateBindings;
use Ray\MediaQuery\Exception\WebApiRequestException;

use function json_decode;
use function uri_template;

final class WebApiQuery implements WebApiQueryInterface
{
    /** @var ClientInterface */
    private $client;

    /** @var MediaQueryLoggerInterface  */
    private $logger;

    /** @var array<string, string>  */
    private $uriTemplateBindings;

    /**
     * @param array<string, string> $uriTemplateBindings
     *
     * @UriTemplateBindings("uriTemplateBindings")
     */
    #[UriTemplateBindings('uriTemplateBindings')]
    public function __construct(ClientInterface $client, MediaQueryLoggerInterface $logger, array $uriTemplateBindings)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->uriTemplateBindings = $uriTemplateBindings;
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
            $body = json_decode($json, true);
            $this->logger->log($boundUri, $query);

            return $body;
        } catch (GuzzleException $e) {
            throw new WebApiRequestException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
