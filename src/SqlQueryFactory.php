<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use Pagerfanta\View\DefaultView;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactoryInterface;

class SqlQueryFactory
{
    /**
     * @param array<string, mixed> $options
     */
    public static function getInstance(
        string $sqlDir,
        string $dsn,
        string $username = '',
        string $password = '',
        array $options = [],
        ?MediaQueryLoggerInterface $logger = null,
        ?AuraSqlPagerFactoryInterface $pagerFactory = null
    ): SqlQueryInterface {
        return new SqlQuery(
            new ExtendedPdo($dsn, $username, $password, $options),
            $sqlDir,
            $logger ?? new MediaQueryLogger(),
            $pagerFactory ?? new AuraSqlPagerFactory(new AuraSqlPager(new DefaultView(), [])),
            new ParamConverter()
        );
    }
}
