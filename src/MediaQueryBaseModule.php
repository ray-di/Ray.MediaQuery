<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeImmutable;
use DateTimeInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class MediaQueryBaseModule extends AbstractModule
{
    /** @var Queries */
    private $queries;

    public function __construct(Queries $queries, ?AbstractModule $module = null)
    {
        $this->queries = $queries;
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
    }
}
