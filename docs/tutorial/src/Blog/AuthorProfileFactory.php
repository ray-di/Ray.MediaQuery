<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use DateTimeImmutable;
use DateTimeInterface;

final class AuthorProfileFactory
{
    public function __construct(
        private readonly DateTimeInterface $now,
    ) {
    }

    public function factory(int $id, string $name, string $birthDate): AuthorProfile
    {
        $age = (new DateTimeImmutable($birthDate))->diff($this->now)->y;

        return new AuthorProfile(
            id: $id,
            name: $name,
            birthDate: $birthDate,
            age: $age,
        );
    }
}
