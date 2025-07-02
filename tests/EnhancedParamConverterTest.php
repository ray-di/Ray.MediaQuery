<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTime;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Exception\PropertyNameConflictException;

class EnhancedParamConverterTest extends TestCase
{
    private EnhancedParamConverter $converter;
    private ParamConverterInterface $baseConverter;

    protected function setUp(): void
    {
        $this->baseConverter = new ParamConverter();
        $this->converter = new EnhancedParamConverter($this->baseConverter);
    }

    public function testFlattensSimpleInputObject(): void
    {
        $userInput = new UserInput(
            givenName: 'John',
            familyName: 'Doe',
            email: 'john@example.com',
        );

        $values = ['user' => $userInput];
        ($this->converter)($values);

        $expected = [
            'givenName' => 'John',
            'familyName' => 'Doe',
            'email' => 'john@example.com',
        ];

        $this->assertSame($expected, $values);
    }

    public function testFlattensNestedInputObjects(): void
    {
        $assignee = new UserInput(
            givenName: 'Jane',
            familyName: 'Smith',
            email: 'jane@example.com',
        );

        $dueDate = new DateTime('2024-01-15 10:00:00');

        $todoInput = new TodoCreateInput(
            title: 'Buy milk',
            assignee: $assignee,
            dueDate: $dueDate,
        );

        $values = ['todo' => $todoInput];
        ($this->converter)($values);

        // 階層を無視してフラット化され、DateTimeも変換される
        $expected = [
            'title' => 'Buy milk',
            'givenName' => 'Jane',      // assigneeから直接展開
            'familyName' => 'Smith',    // assigneeから直接展開
            'email' => 'jane@example.com', // assigneeから直接展開
            'dueDate' => '2024-01-15 10:00:00', // ParamConverterで変換済み
        ];

        $this->assertSame($expected, $values);
    }

    public function testMixedInputAndRegularValues(): void
    {
        $userInput = new UserInput(
            givenName: 'John',
            familyName: 'Doe',
            email: 'john@example.com',
        );

        $values = [
            'user' => $userInput,
            'config' => 'setting_value',  // スカラー値はそのまま
            'title' => 'Test',            // スカラー値はそのまま
        ];

        ($this->converter)($values);

        $expected = [
            'givenName' => 'John',
            'familyName' => 'Doe',
            'email' => 'john@example.com',
            'config' => 'setting_value',  // スカラー値のまま
            'title' => 'Test',
        ];

        $this->assertSame($expected, $values);
    }

    public function testMultipleInputObjectsWithSameProperty(): void
    {
        $user1 = new UserInput(
            givenName: 'John',
            familyName: 'Doe',
            email: 'john@example.com',
        );

        $user2 = new UserInput(
            givenName: 'Jane',
            familyName: 'Smith',
            email: 'jane@example.com',
        );

        $values = [
            'user1' => $user1,
            'user2' => $user2,
        ];

        // 名前衝突が発生するのでエラーになることを期待
        $this->expectException(PropertyNameConflictException::class);
        $this->expectExceptionMessage('givenName');

        ($this->converter)($values);
    }

    public function testPreservesExistingParamConverterBehavior(): void
    {
        // 既存のParamConverterの動作が保持されることをテスト
        $values = [
            'date_val' => new UnixEpocTime(),
            'bool_val' => new FakeBool(),
            'string_val' => new FakeString(),
            'array_val' => new FakeArray(),
        ];

        ($this->converter)($values);

        $expected = [
            'date_val' => UnixEpocTime::TEXT,
            'bool_val' => true,
            'string_val' => 'a',
            'array_val' => [0, 1],
        ];

        $this->assertSame($expected, $values);
    }
}
