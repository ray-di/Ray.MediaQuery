<?php

declare(strict_types=1);

namespace Ray\MediaQuery\SemanticLog\Context;

use Koriym\SemanticLogger\AbstractContext;

final class DatabaseContext extends AbstractContext
{
    public const TYPE = 'database';
    public const SCHEMA_URL = 'https://ray-di.github.io/Ray.MediaQuery/schemas/database.json';

    public function __construct(
        public readonly string $operation,
        public readonly string $dsn,
        public readonly float|null $executionTime = null,
        public readonly int|null $affectedRows = null,
    ) {
    }
}
