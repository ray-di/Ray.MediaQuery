<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Di\AbstractModule;
use Ray\MediaQuery\DbQuery\DbQueryConfig;
use Ray\MediaQuery\DbQuery\Queries;
use Ray\MediaQuery\WebQuery\WebQueryConfig;

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
        $this->install(new MediaQueryBaseModule($this->queries));
        foreach ($this->configs as $config) {
            if ($config instanceof DbQueryConfig) {
                $this->install(new MediaQueryDbModule($config));
                continue;
            }

            $this->install(new MediaQueryWebModule($config));
        }
    }
}
