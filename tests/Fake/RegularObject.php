<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

// Input属性なしの通常オブジェクト（フラット化されないことをテストするため）
final class RegularObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $value
    ) {}
}