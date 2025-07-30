<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use Ray\Di\AbstractModule;

final class MediaQueryModule extends AbstractModule
{
    /** @param list<DbQueryConfig> $configs */
    public function __construct(
        private Queries $queries,
        private array $configs,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->install(new MediaQueryBaseModule($this->queries));
        foreach ($this->configs as $config) {
            $this->install(new MediaQueryDbModule($config));
        }
    }
}
