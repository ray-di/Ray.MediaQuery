<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Qualifier\FactoryMethod;
use Ray\MediaQuery\Annotation\Qualifier\SqlDir;

/**
 * Database query module - provides SQL-specific bindings.
 *
 * This is an internal implementation module. Users should use MediaQuerySqlModule
 * instead of using this module directly.
 *
 * @internal
 */
final class MediaQueryDbModule extends AbstractModule
{
    public function __construct(
        private DbQueryConfig $configs,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(PerformSqlInterface::class)->to(PerformSql::class);
        $this->bind(SqlQueryInterface::class)->to(SqlQuery::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(DbQuery::class),
            [DbQueryInterceptor::class],
        );
        $this->bind()->annotatedWith(SqlDir::class)->toInstance($this->configs->sqlDir);
        $this->bind(DocBlockFactoryInterface::class)->toInstance(DocBlockFactory::createInstance());
        $this->bind(ReturnEntityInterface::class)->to(ReturnEntity::class);
        $this->bind(FetchFactoryInterface::class)->to(FetchFactory::class);
        $this->bind(PageRowMapperFactory::class);
        $this->bind()->annotatedWith(FactoryMethod::class)->toInstance('factory');
        $this->bind(DbPager::class);
    }
}
