<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\PHPStan\Support\NamedParameterExtractor;

final class NamedParameterExtractorTest extends TestCase
{
    /** @param list<string> $expected */
    #[DataProvider('sqlProvider')]
    public function testExtract(string $sql, array $expected): void
    {
        self::assertSame($expected, (new NamedParameterExtractor())->extract($sql));
    }

    /** @return iterable<string, array{0:string, 1:list<string>}> */
    public static function sqlProvider(): iterable
    {
        yield 'simple' => ['SELECT * FROM todo WHERE id = :id', ['id']];
        yield 'string literal' => ['SELECT \':ignored\', id FROM todo WHERE id = :id', ['id']];
        yield 'line comment' => ["-- :ignored\nSELECT * FROM todo WHERE id = :id", ['id']];
        yield 'block comment' => ['SELECT /* :ignored */ * FROM todo WHERE id = :id', ['id']];
        yield 'postgres cast' => ['SELECT created_at::date FROM todo WHERE id = :id', ['id']];
        yield 'mysql assignment' => ['SELECT @x := 1, id FROM todo WHERE id = :id', ['id']];
        yield 'duplicates' => ['UPDATE todo SET parent_id = :id WHERE id = :id', ['id']];
        yield 'multiple statements' => ["SELECT 'a;b' FROM todo WHERE id = :id; UPDATE todo SET title = :title", ['id', 'title']];
        yield 'quoted identifiers' => ['SELECT `:ignored`, ":alsoIgnored", [stillIgnored] FROM todo WHERE id = :id', ['id']];
        yield 'camel and snake' => ['INSERT INTO memo VALUES (:user_id, :todoId)', ['user_id', 'todoId']];
    }
}
