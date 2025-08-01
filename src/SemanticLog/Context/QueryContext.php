<?php

declare(strict_types=1);

namespace Ray\MediaQuery\SemanticLog\Context;

use Koriym\SemanticLogger\AbstractContext;

final class QueryContext extends AbstractContext
{
    public const TYPE = 'query';
    public const SCHEMA_URL = 'https://ray-di.github.io/Ray.MediaQuery/schemas/query.json';

    public function __construct(
        public readonly string $queryId,
        public readonly string $operation,
        public readonly string|null $sqlFile = null,
        public readonly string|null $sqlContent = null,
    ) {
    }
}
