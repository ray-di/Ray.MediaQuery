<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Exception\WebApiRequestException;

class WebQueryTest extends TestCase
{
    public function testRequest(): void
    {
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), ['domain1' => 'ray-di.github.io']);
        $response = $webQuery->request('GET', 'https://{domain1}/Ray.MediaQuery/schema/{id}.json', ['id' => 'web_query']);
        $this->assertSame('Web query schema', $response['title']);
    }

    public function testGetStringBody(): void
    {
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), ['domain1' => 'ray-di.github.io']);
        $response = $webQuery->getStringBody('GET', 'https://{domain1}/Ray.MediaQuery/schema/{id}.json', ['id' => 'web_query']);
        $this->assertStringContainsString('"title": "Web query schema"', $response);
    }

    public function testGetHttpMessage(): void
    {
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), ['domain1' => 'ray-di.github.io']);
        $response = $webQuery->getHttpMessage('GET', 'https://{domain1}/Ray.MediaQuery/schema/{id}.json', ['id' => 'web_query']);
        $this->assertStringContainsString('"title": "Web query schema"', $response->getBody()->getContents());
    }

    public function testInvalidRequest(): void
    {
        $this->expectException(WebApiRequestException::class);
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), []);
        $webQuery->request('GET', 'https://__invalid__/', []);
    }

    public function testInvalidRequestStringBody(): void
    {
        $this->expectException(WebApiRequestException::class);
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), []);
        $webQuery->getStringBody('GET', 'https://__invalid__/', []);
    }

    public function testInvalidRequestHttpMessage(): void
    {
        $this->expectException(WebApiRequestException::class);
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), []);
        $webQuery->getHttpMessage('GET', 'https://__invalid__/', []);
    }
}
