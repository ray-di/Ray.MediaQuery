<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\WebApi\FooItemInterface;

use function dirname;

class WebQueryModuleTest extends TestCase
{
    /** @var AbstractModule */
    protected $module;

    /** @var MediaQueryLoggerInterface */
    private $logger;

    /** @var Injector */
    private $injector;

    protected function setUp(): void
    {
        $mediaQueries = Queries::fromClasses([FooItemInterface::class]);
        $sqlDir = dirname(__DIR__) . '/tests/sql';
        $uriBindings = ['domain' => 'httpbin.org'];
        $module = new MediaQueryModule($mediaQueries, $sqlDir, $uriBindings);
        $this->injector = new Injector($module);
        $this->logger = $this->injector->getInstance(MediaQueryLoggerInterface::class);
    }

    public function testGetRequest(): void
    {
        $fooItem = $this->injector->getInstance(FooItemInterface::class);
        $response = ($fooItem)('hello');
        $this->assertSame('https://httpbin.org/anything/hello', $response['url']);
        $this->assertSame('query: https://httpbin.org/anything/hello({"id":"hello"})', (string) $this->logger);
    }
}
