<?php

declare(strict_types=1);

namespace Ray\MediaQuery\SemanticLog\Context;

use Koriym\SemanticLogger\AbstractContext;

final class ResultContext extends AbstractContext
{
    public const TYPE = 'result';
    public const SCHEMA_URL = 'https://ray-di.github.io/Ray.MediaQuery/schemas/result.json';

    public function __construct(
        public readonly string $status,
        public readonly string|null $error = null,
        public readonly array|null $metadata = null,
        public readonly mixed $result = null,
        public readonly int|null $resultCount = null,
    ) {
    }
}
