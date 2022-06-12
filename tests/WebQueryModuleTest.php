<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\DbQuery\Queries;
use Ray\MediaQuery\WebApi\FooItemInterface;
use Ray\MediaQuery\WebQuery\WebQueryConfig;

use function assert;
use function is_callable;

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
        $mediaQueryJson = __DIR__ . '/Fake/web_query.json';
        $module = new MediaQueryModule($mediaQueries, [new WebQueryConfig($mediaQueryJson, ['domain' => 'httpbin.org'])]);
        $this->injector = new Injector($module);
        $logger = $this->injector->getInstance(MediaQueryLoggerInterface::class);
        assert($logger instanceof MediaQueryLoggerInterface);
        $this->logger = $logger;
    }

    public function testGetRequest(): void
    {
        $fooItem = $this->injector->getInstance(FooItemInterface::class);
        assert(is_callable($fooItem));
        $response = ($fooItem)('hello');
        $this->assertSame('https://httpbin.org/anything/hello', $response['url']);
        $this->assertSame('query: https://httpbin.org/anything/hello({"id":"hello"})', (string) $this->logger);
    }
}
