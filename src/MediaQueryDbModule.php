<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Di\AbstractModule;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\SqlDir;
use Ray\MediaQuery\DbQuery\DbQueryConfig;
use Ray\MediaQuery\DbQuery\DbQueryInterceptor;
use Ray\MediaQuery\DbQuery\SqlQuery;
use Ray\MediaQuery\DbQuery\SqlQueryInterface;

class MediaQueryDbModule extends AbstractModule
{
    /** @var DbQueryConfig */
    private $configs;

    public function __construct(DbQueryConfig $config, ?AbstractModule $module = null)
    {
        $this->configs = $config;
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(SqlQueryInterface::class)->to(SqlQuery::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(DbQuery::class),
            [DbQueryInterceptor::class]
        );
        $this->bind()->annotatedWith(SqlDir::class)->toInstance($this->configs->sqlDir);
    }
}
