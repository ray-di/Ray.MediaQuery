<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\MediaQuery\Annotation\QueryId;
use Ray\MediaQuery\Annotation\SqlDir;

class MediaQueryModule extends AbstractModule
{
    /** @var string */
    private $sqlDir;
    public function __construct(string $sqlDir, ?AbstractModule $module = null)
    {
        $this->sqlDir = $sqlDir;
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(SqlQueryInterface::class)->to(SqlQuery::class);
        $this->bind(MediaQueryLoggerInterface::class)->to(MediaQueryLogger::class)->in(Scope::SINGLETON);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(QueryId::class),
            [MediaQueryInterceptor::class]
        );
        $this->bind()->annotatedWith(SqlDir::class)->toInstance($this->sqlDir);
    }
}
