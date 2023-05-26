<?php
declare(strict_types=1);

namespace Ray\MediaQuery\Entity;

final class Memo
{
    public function __construct(
        public string $id,
        public string $body
    ) {
    }
}
