<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Di\AbstractModule;

class ApiDomainModule extends AbstractModule
{
    /** @var array<string, string> */
    private $domainBindings;

    /**
     * @param array<string, string> $domainBindings
     */
    public function __construct(array $domainBindings, ?AbstractModule $module = null)
    {
        $this->domainBindings = $domainBindings;
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind()->annotatedWith('web_api_query_domain')->toInstance($this->domainBindings);
    }
}
