<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\WebApi\FooItemInterface;

use function assert;
use function is_callable;

class WebQueryModuleTest extends TestCase
{
    protected AbstractModule $module;
    private MediaQueryLoggerInterface $logger;
    private Injector $injector;

    protected function setUp(): void
    {
        $mediaQueries = Queries::fromClasses([FooItemInterface::class]);
        $mediaQueryJson = __DIR__ . '/Fake/web_query.json';
        $module = new MediaQueryModule($mediaQueries, [new WebQueryConfig($mediaQueryJson, ['domain' => 'ray-di.github.io'])]);
        $this->injector = new Injector($module);
        $logger = $this->injector->getInstance(MediaQueryLoggerInterface::class);
        assert($logger instanceof MediaQueryLoggerInterface);
        $this->logger = $logger;
    }

    public function testGetRequest(): void
    {
        $fooItem = $this->injector->getInstance(FooItemInterface::class);
        assert(is_callable($fooItem));
        $response = ($fooItem)('web_query');
        $this->assertSame('Web query schema', $response['title']);
        $this->assertSame('query: https://ray-di.github.io/Ray.MediaQuery/schema/web_query.json({"id":"web_query"})', (string) $this->logger);
    }
}
