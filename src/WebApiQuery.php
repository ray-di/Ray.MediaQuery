<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use Override;
use Psr\Http\Message\MessageInterface;
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
        #[UriTemplateBindings]
        private array $uriTemplateBindings,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function request(string $method, string $uri, array $query): array
    {
        try {
            $response = $this->executeRequest($method, $uri, $query);
            $json = $response->getBody()->getContents();
            /** @var array<string, mixed> $body */
            $body = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return $body;
        } catch (TransferException $e) {
            throw new WebApiRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getStringBody(string $method, string $uri, array $query): string
    {
        try {
            $response = $this->executeRequest($method, $uri, $query);

            return $response->getBody()->getContents();
        } catch (TransferException $e) {
            throw new WebApiRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getHttpMessage(string $method, string $uri, array $query): MessageInterface
    {
        try {
            return $this->executeRequest($method, $uri, $query);
        } catch (TransferException $e) {
            throw new WebApiRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, string> $query
     *
     * @throws GuzzleException
     */
    private function executeRequest(string $method, string $uri, array $query): MessageInterface
    {
        $this->logger->start();
        $boundUri = uri_template($uri, $this->uriTemplateBindings + $query);
        $response = $this->client->request($method, $boundUri, $query);
        $this->logger->log($boundUri, $query);

        return $response;
    }
}
