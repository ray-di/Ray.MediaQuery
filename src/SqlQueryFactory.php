<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Aura\Sql\ExtendedPdo;
use Pagerfanta\View\DefaultView;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPager;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactory;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerFactoryInterface;
use Ray\Di\InjectorInterface;

final class SqlQueryFactory
{
    /** @param array<string, mixed> $options */
    public static function getInstance(
        string $sqlDir,
        string $dsn,
        InjectorInterface $injector,
        string $password = '',
        string $username = '',
        MediaQueryLoggerInterface|null $logger = null,
        AuraSqlPagerFactoryInterface|null $pagerFactory = null,
        array $options = [],
    ): SqlQuery {
        return new SqlQuery(
            new ExtendedPdo($dsn, $username, $password, $options),
            $sqlDir,
            $logger ?? new MediaQueryLogger(),
            $pagerFactory ?? new AuraSqlPagerFactory(new AuraSqlPager(new DefaultView(), [])),
            new ParamConverter(),
            $injector,
        );
    }
}
