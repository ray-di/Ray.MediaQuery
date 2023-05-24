<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Di\AbstractModule;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Qualifier\FactoryMethod;
use Ray\MediaQuery\Annotation\Qualifier\SqlDir;

class MediaQueryDbModule extends AbstractModule
{
    public function __construct(
        private DbQueryConfig $configs,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(SqlQueryInterface::class)->to(SqlQuery::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(DbQuery::class),
            [DbQueryInterceptor::class],
        );
        $this->bind()->annotatedWith(SqlDir::class)->toInstance($this->configs->sqlDir);
        $this->bind(ReturnEntityInterface::class)->to(ReturnEntity::class);
        $this->bind(FetchFactoryInterface::class)->to(FetchFactory::class);
        $this->bind()->annotatedWith(FactoryMethod::class)->toInstance('factory');
    }
}
