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
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), ['domain1' => 'httpbin.org']);
        $response = $webQuery->request('GET', 'https://{domain1}/anything/foo', ['id' => '1']);
        $this->assertSame('GET', $response['method']);
    }

    public function testInvalidRequest(): void
    {
        $this->expectException(WebApiRequestException::class);
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), []);
        $webQuery->request('GET', 'https://__invalid__/', []);
    }
}
