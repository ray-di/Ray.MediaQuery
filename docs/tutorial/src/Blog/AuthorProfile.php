<?php

declare(strict_types=1);

namespace Tutorial\Blog;

final class AuthorProfile
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $birthDate,
        public readonly int $age,
    ) {
    }
}
