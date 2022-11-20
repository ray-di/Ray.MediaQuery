<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Ray\Di\AbstractModule;

class MediaQueryModule extends AbstractModule
{
    /** @param list<DbQueryConfig|WebQueryConfig> $configs */
    public function __construct(
        private Queries $queries,
        private array $configs,
        AbstractModule|null $module = null,
    ) {
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
