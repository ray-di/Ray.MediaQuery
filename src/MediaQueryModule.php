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
    /** @var Queries */
    private $queries;

    /** @var list<DbQueryConfig|WebQueryConfig> */
    private $configs;

    /**
     * @param list<DbQueryConfig|WebQueryConfig> $configs
     */
    public function __construct(Queries $queries, array $configs, ?AbstractModule $module = null)
    {
        $this->queries = $queries;
        $this->configs = $configs;
        parent::__construct($module);
    }

    protected function configure(): void
    {
        foreach ($this->queries->classes as $class) {
            $this->bind($class)->toNull();
        }

        $this->bind(MediaQueryLoggerInterface::class)->to(MediaQueryLogger::class)->in(Scope::SINGLETON);
        $this->bind(ParamInjectorInterface::class)->to(ParamInjector::class);
        $this->bind(ParamConverterInterface::class)->to(ParamConverter::class);
        $this->bind(DateTimeInterface::class)->to(DateTimeImmutable::class);
        foreach ($this->configs as $config) {
            if ($config instanceof DbQueryConfig) {
                $this->configureDbQuery($config);
                continue;
            }

            $this->configureWebQuery($config);
        }
    }

    private function configureDbQuery(DbQueryConfig $dbQuery): void
    {
        // DbQueryConfig
        $this->bind(SqlQueryInterface::class)->to(SqlQuery::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(DbQuery::class),
            [DbQueryInterceptor::class]
        );
        $this->bind()->annotatedWith(SqlDir::class)->toInstance($dbQuery->sqlDir);
    }

    private function configureWebQuery(WebQueryConfig $webQueryConfig): void
    {
        // Web Query
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(WebQuery::class),
            [WebQueryInterceptor::class]
        );
        $this->bind(ClientInterface::class)->to(Client::class);
        $this->bind(WebApiQueryInterface::class)->to(WebApiQuery::class);
        $config = [];
        foreach ($webQueryConfig->apis as $id => $item) {
            $config[$id] = ['method' => $item['method'], 'path' => $item['path']];
        }

        $this->bind()->annotatedWith('media_query_config')->toInstance($config);
        $this->bind()->annotatedWith('web_api_query_domain')->toInstance($webQueryConfig->urlTemplateBindings);
    }
}
