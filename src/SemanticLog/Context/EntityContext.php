<?php

declare(strict_types=1);

namespace Ray\MediaQuery\SemanticLog\Context;

use Koriym\SemanticLogger\AbstractContext;

final class EntityContext extends AbstractContext
{
    public const TYPE = 'entity';
    public const SCHEMA_URL = 'https://ray-di.github.io/Ray.MediaQuery/schemas/entity.json';

    public function __construct(
        public readonly string $operation,
        public readonly string $entityClass,
        public readonly string $fetchMethod,
        public readonly int|null $entityCount = null,
        public readonly string|null $factoryClass = null,
    ) {
    }
}
