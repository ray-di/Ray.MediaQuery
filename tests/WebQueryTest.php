<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class WebQueryTest extends TestCase
{
    public function testRequest(): void
    {
        $webQuery = new WebApiQuery(new Client(), new MediaQueryLogger(), ['domain1' => 'httpbin.org']);
        $response = $webQuery->request('GET', 'https://{domain1}/anything/foo', ['id' => '1']);
        $this->assertSame('GET', $response['method']);
    }
}
