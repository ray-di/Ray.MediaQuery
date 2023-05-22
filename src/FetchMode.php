<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

final class FetchMode
{
    /**
     * @param int                                   $mode
     * @param array{0:string, 1:string}|string|null $args
     */
    public function __construct(
        public int $mode,
        public array|string|null $args,
    ) {
    }
}
