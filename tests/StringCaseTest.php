<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;

class StringCaseTest extends TestCase
{
    public function testCamel(): void
    {
        $this->assertSame('userName', StringCase::camel('user_name'));
        $this->assertSame('id', StringCase::camel('id'));
        $this->assertSame('createdAt', StringCase::camel('created_at'));
        $this->assertSame('apiKey', StringCase::camel('api_key'));
    }

    public function testSnake(): void
    {
        $this->assertSame('user_name', StringCase::snake('userName'));
        $this->assertSame('id', StringCase::snake('id'));
        $this->assertSame('created_at', StringCase::snake('createdAt'));
        $this->assertSame('api_key', StringCase::snake('apiKey'));
    }
}
