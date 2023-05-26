<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PDO;
use PDOStatement;
use Ray\Di\InjectorInterface;

final class FetchStaticFactory implements FetchInterface
{
    /** @var callable */
    private $staticFactory;

    public function __construct(
        callable $staticFactory,
    ) {
        $this->staticFactory = $staticFactory;
    }

    /** @return array<mixed> */
    public function fetchAll(PDOStatement $pdoStatement, InjectorInterface $injector): array
    {
        // 'factory' attribute
        return $pdoStatement->fetchAll(PDO::FETCH_FUNC, $this->staticFactory);
    }
}
