<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\WebApi\FooItemInterface;

use function assert;

class WebQueryModuleTest extends TestCase
{
    protected AbstractModule $module;
    private MediaQueryLoggerInterface $logger;
    private Injector $injector;
    private FooItemInterface $fooItem;

    protected function setUp(): void
    {
        $mediaQueries = Queries::fromClasses([FooItemInterface::class]);
        $mediaQueryJson = __DIR__ . '/Fake/web_query.json';
        $module = new MediaQueryModule($mediaQueries, [new WebQueryConfig($mediaQueryJson, ['domain' => 'ray-di.github.io'])]);
        $this->injector = new Injector($module);
        $logger = $this->injector->getInstance(MediaQueryLoggerInterface::class);
        assert($logger instanceof MediaQueryLoggerInterface);
        $this->logger = $logger;
        $this->fooItem = $this->injector->getInstance(FooItemInterface::class);
    }

    public function testGetRequest(): void
    {
        $response = $this->fooItem->item('web_query');
        $this->assertSame('Web query schema', $response['title']);
        $this->assertSame('query: https://ray-di.github.io/Ray.MediaQuery/schema/web_query.json({"id":"web_query"})', (string) $this->logger);
    }

    public function testGetRequestStringResponse(): void
    {
        $response = $this->fooItem->body('web_query');
        $this->assertStringContainsString('"title": "Web query schema"', $response);
    }

    public function testGetRequestHttpMessageResponse(): void
    {
        $response = $this->fooItem->message('web_query');
        $this->assertInstanceOf(MessageInterface::class, $response);
        $this->assertStringContainsString('"title": "Web query schema"', $response->getBody()->getContents());
    }
}
