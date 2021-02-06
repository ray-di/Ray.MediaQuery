<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\SqlDir;

class MediaQueryModule extends AbstractModule
{
    /** @var string */
    private $sqlDir;

    /** @var array<class-string> */
    private $mediaQueries;
    private string $dsn;

    /**
     * @param array<class-string> $mediaQueries
     */
    public function __construct(string $dsn, string $sqlDir, array $mediaQueries, ?AbstractModule $module = null)
    {
        $this->mediaQueries = $mediaQueries;
        $this->sqlDir = $sqlDir;
        $this->dsn = $dsn;
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->install(new AuraSqlModule($this->dsn));
        $this->bind(SqlQueryInterface::class)->to(SqlQuery::class);
        $this->bind(MediaQueryLoggerInterface::class)->to(MediaQueryLogger::class)->in(Scope::SINGLETON);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(DbQuery::class),
            [MediaQueryInterceptor::class]
        );
        $this->bind()->annotatedWith(SqlDir::class)->toInstance($this->sqlDir);
        // Bind media query interface
        foreach ($this->mediaQueries as $mediaQuery) {
            $this->bind($mediaQuery)->to($mediaQuery . 'Null');
        }
    }
}
