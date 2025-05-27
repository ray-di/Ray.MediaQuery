<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;

use function base64_encode;
use function implode;
use function sprintf;

use const PHP_EOL;

final class MediaQueryLoggerTest extends TestCase
{
    public function testLogWithValidUtf8(): void
    {
        $logger = new MediaQueryLogger();
        $logger->log('query1', ['msg1' => 'hello', 'msg2' => 'world']);

        $expected = 'query: query1({"msg1":"hello","msg2":"world"})';
        $this->assertCount(1, $logger->logs);
        $this->assertSame($expected, $logger->logs[0]);
    }

    public function testLogWithNonUtf8StringShouldBeBase64Encoded(): void
    {
        $logger = new MediaQueryLogger();

        $invalidString = "\x80\x81";
        $logger->log('query2', ['msg1' => $invalidString]);

        $encoded = base64_encode($invalidString);

        $expected = sprintf('query: query2({"msg1":"%s"})', $encoded);
        $this->assertCount(1, $logger->logs);
        $this->assertSame($expected, $logger->logs[0]);
    }

    public function testToString(): void
    {
        $logger = new MediaQueryLogger();
        $logger->log('query1', ['msg1' => 'foo', 'msg2' => 'bar']);
        $logger->log('query2', ['msg1' => 'baz']);

        $expected = implode(PHP_EOL, [
            'query: query1({"msg1":"foo","msg2":"bar"})',
            'query: query2({"msg1":"baz"})',
        ]);
        $this->assertSame($expected, (string) $logger);
    }
}
