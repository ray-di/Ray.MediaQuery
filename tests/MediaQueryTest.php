<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;

class MediaQueryTest extends TestCase
{
    /** @var AbstractModule */
    protected $module;

    protected function setUp(): void
    {
        $this->module = new MediaQueryModule();
    }

    public function testIsInstanceOfMediaQuery(): void
    {
        $injector = new Injector($this->module);
        $userAdd = $injector->getInstance(UserAddInterface::class);
        assert($userAdd instanceof UserAddInterface);
        $log = $injector->getInstance(MediaQueryLog::class);
        assert($log instanceof MediaQueryLog);
        $userAdd('ray', 10);
        $this->assertStringContainsString('request:user_add', (string) $log);
    }
}
