<?php

declare(strict_types=1);

namespace Ray\MediaQuery\SemanticLog\Context;

use Koriym\SemanticLogger\AbstractContext;

final class EventContext extends AbstractContext
{
    public const TYPE = 'event';
    public const SCHEMA_URL = 'https://ray-di.github.io/Ray.MediaQuery/schemas/event.json';

    public function __construct(
        public readonly string $message,
        public readonly string $level = 'info',
        public readonly array|null $data = null,
    ) {
    }
}
