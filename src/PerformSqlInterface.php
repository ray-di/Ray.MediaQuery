<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdoInterface;
use PDOStatement;

interface PerformSqlInterface
{
    /**
     * Perform SQL with the given parameters
     *
     * @param ExtendedPdoInterface $pdo    The PDO instance to perform the SQL on.
     * @param string               $sqlId  The identifier for the SQL statement.
     * @param string               $sql    The SQL statement to be executed.
     * @param array<string, mixed> $values The values to bind to the SQL statement.
     *
     * @return PDOStatement The result of the performed SQL statement.
     */
    public function perform(ExtendedPdoInterface $pdo, string $sqlId, string $sql, array $values): PDOStatement;
}
