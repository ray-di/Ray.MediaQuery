<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use Override;
use PDOStatement;

final class PerformSql implements PerformSqlInterface
{
    /** @psalm-taint-escape sql */
    #[Override]
    public function perform(ExtendedPdoInterface $pdo, string $sqlId, string $sql, array $values): PDOStatement
    {
        unset($sqlId);

        /** @var array<string, mixed> $values */
        return $pdo->perform($sql, $values);
    }
}
