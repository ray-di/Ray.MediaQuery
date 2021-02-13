<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use function array_diff;
use function array_pop;
use function count;
use function get_declared_interfaces;

class GetClassName
{
    public function __invoke(string $file): string
    {
        $interfaces = get_declared_interfaces();
        /** @psalm-suppress UnresolvableInclude */
        include_once $file;
        $diff = array_diff(get_declared_interfaces(), $interfaces);
        if (count($diff)) {
            return array_pop($diff);
        }

        return '';
    }
}
