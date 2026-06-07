<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Result\InsertedRow;
use Ray\MediaQuery\Result\PostQueryContext;

final class InsertedRowTest extends TestCase
{
    /**
     * @param string|false $lastInsertId
     */
    #[DataProvider('emptyLastInsertIds')]
    public function testFromContextNormalizesEmptyLastInsertId(string|false $lastInsertId): void
    {
        $pdo = $this->createStub(ExtendedPdoInterface::class);
        $pdo->method('lastInsertId')->willReturn($lastInsertId);

        $result = InsertedRow::fromContext(new PostQueryContext(
            $this->createStub(PDOStatement::class),
            $pdo,
            ['label' => 'empty'],
        ));

        $this->assertSame(['label' => 'empty'], $result->values);
        $this->assertNull($result->id);
    }

    /**
     * @return array<string, array{string|false}>
     */
    public static function emptyLastInsertIds(): array
    {
        return [
            'false' => [false],
            'empty string' => [''],
            'zero string' => ['0'],
        ];
    }
}
