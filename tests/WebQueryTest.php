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

    public function testInvalidRequest(): void
    {
        $this->expectException(WebApiRequestException::class);
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), []);
        $webQuery->request('GET', 'https://__invalid__/', []);
    }
}
