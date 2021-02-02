<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;

interface SqlQueryInterface
{
    public function exec(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): void;

    /**
     * @param array<string, string> $params
     *
     * @return array<string>
     */
    public function getRow(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array;

    /**
     * @param array<string, string> $params
     *
     * @return array<array<string>>
     */
    public function getRowList(string $sqlId, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array;

    public function getStatement(): PDOStatement;
}
