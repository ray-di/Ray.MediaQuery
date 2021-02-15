<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeImmutable;
use DateTimeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\SqlDir;
use Ray\MediaQuery\Annotation\WebQuery;

class MediaQueryModule extends AbstractModule
{
    /** @var string */
    private $sqlDir;

    /** @var list<class-string> */
    private $mediaQueries;

    /** @var array<string, string> */
    private array $domainBindings;

    /**
     * @param Queries               $mediaQueries
     * @param string                $sqlDir
     * @param array<string, string> $domainBindings
     * @param AbstractModule|null   $module
     */
    public function __construct(Queries $mediaQueries, string $sqlDir, array $domainBindings = [], ?AbstractModule $module = null)
    {
        $this->mediaQueries = $mediaQueries->classes;
        $this->sqlDir = $sqlDir;
        $this->domainBindings = $domainBindings;
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(MediaQueryLoggerInterface::class)->to(MediaQueryLogger::class)->in(Scope::SINGLETON);
        $this->bind(ParamInjectorInterface::class)->to(ParamInjector::class);
        $this->bind(ParamConverterInterface::class)->to(ParamConverter::class);
        $this->bind(DateTimeInterface::class)->to(DateTimeImmutable::class);
        // Bind media query interface
        foreach ($this->mediaQueries as $mediaQuery) {
            $this->bind($mediaQuery)->toNull();
        }

        // DbQuery
        $this->bind(SqlQueryInterface::class)->to(SqlQuery::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(DbQuery::class),
            [DbQueryInterceptor::class]
        );
        $this->bind()->annotatedWith(SqlDir::class)->toInstance($this->sqlDir);
        // Web Query
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(WebQuery::class),
            [WebQueryInterceptor::class]
        );
        $this->bind(ClientInterface::class)->to(Client::class);
        $this->bind(WebApiQueryInterface::class)->to(WebApiQuery::class);
        $this->bind()->annotatedWith('web_api_query_domain')->toInstance($this->domainBindings);
    }
}
