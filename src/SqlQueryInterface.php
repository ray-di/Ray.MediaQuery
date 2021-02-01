<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

interface SqlQueryInterface
{
    /**
     * @param array<string, string> $params
     *
     * @return mixed
     */
    public function __invoke(string $sqlFile, array $params);
}
