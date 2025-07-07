<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeImmutable;
use DateTimeInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\InputQuery\ToArray;
use Ray\InputQuery\ToArrayInterface;

final class MediaQueryBaseModule extends AbstractModule
{
    public function __construct(
        private Queries $queries,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        foreach ($this->queries->classes as $class) {
            $this->bind($class)->toNull();
        }

        $this->bind(MediaQueryLoggerInterface::class)->to(MediaQueryLogger::class)->in(Scope::SINGLETON);
        $this->bind(ParamInjectorInterface::class)->to(ParamInjector::class);
        $this->bind(ParamConverterInterface::class)->to(ParamConverter::class);
        $this->bind(ParamConverterInterface::class)->annotatedWith('original')->to(ParamConverter::class);
        $this->bind(DateTimeInterface::class)->to(DateTimeImmutable::class);
        $this->bind(ToArrayInterface::class)->to(ToArray::class);
    }
}
