<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

/**
 * Result of a DML execution returned by SqlQueryInterface::execAffected()
 * and DbQuery methods that declare an AffectedRows return type.
 */
final class AffectedRows
{
    /**
     * @param int         $count        Number of rows affected by the last executed statement.
     * @param string|null $lastInsertId Auto-increment id assigned by an INSERT. Null for
     *                                  non-INSERT statements, and also when the driver
     *                                  reports no id (e.g. tables without AUTO_INCREMENT,
     *                                  or values '0' / '' which are normalised to null).
     */
    public function __construct(
        public readonly int $count,
        public readonly string|null $lastInsertId = null,
    ) {
    }

    public function isAffected(): bool
    {
        return $this->count > 0;
    }
}
