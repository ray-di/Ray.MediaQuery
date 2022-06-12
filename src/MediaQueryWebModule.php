<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\Annotation\Qualifier\UriTemplateBindings;
use Ray\MediaQuery\Annotation\Qualifier\WebApiList;
use Ray\MediaQuery\Annotation\WebQuery;
use Ray\MediaQuery\WebQuery\WebApiQuery;
use Ray\MediaQuery\WebQuery\WebApiQueryInterface;
use Ray\MediaQuery\WebQuery\WebQueryConfig;
use Ray\MediaQuery\WebQuery\WebQueryInterceptor;

class MediaQueryWebModule extends AbstractModule
{
    /** @var WebQueryConfig */
    private $config;

    public function __construct(WebQueryConfig $config, ?AbstractModule $module = null)
    {
        $this->config = $config;
        parent::__construct($module);
    }

    public function configure(): void
    {
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(WebQuery::class),
            [WebQueryInterceptor::class]
        );
        $this->bind(ClientInterface::class)->to(Client::class);
        $this->bind(WebApiQueryInterface::class)->to(WebApiQuery::class);
        $config = [];
        foreach ($this->config->apis as $id => $item) {
            $config[$id] = ['method' => $item['method'], 'path' => $item['path']];
        }

        $this->bind()->annotatedWith(WebApiList::class)->toInstance($config);
        $this->bind()->annotatedWith(UriTemplateBindings::class)->toInstance($this->config->urlTemplateBindings);
    }
}
