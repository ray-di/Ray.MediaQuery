<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use Ray\Di\AbstractModule;

/**
 * SQL-based MediaQuery module.
 *
 * This module simplifies SQL query integration by accepting directory paths directly.
 * It automatically discovers query interfaces from the interface directory and configures
 * SQL query execution from the SQL directory.
 *
 * Example usage:
 * ```php
 * $this->install(new MediaQuerySqlModule(
 *     interfaceDir: '/path/to/query/interfaces',
 *     sqlDir: '/path/to/sql/files'
 * ));
 * ```
 *
 * For advanced use cases requiring explicit query class selection or custom configuration,
 * create your own module that wraps the internal modules:
 * ```php
 * class MyQueryModule extends AbstractModule {
 *     protected function configure(): void {
 *         $queries = Queries::fromClasses([UserInterface::class, OrderInterface::class]);
 *         $this->install(new MediaQueryBaseModule($queries));
 *         $this->install(new MediaQueryDbModule(new DbQueryConfig('/path/to/sql')));
 *     }
 * }
 * ```
 */
final class MediaQuerySqlModule extends AbstractModule
{
    public function __construct(
        private readonly string $interfaceDir,
        private readonly string $sqlDir,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $queries = Queries::fromDir($this->interfaceDir);
        $this->install(new MediaQueryBaseModule($queries));
        $this->install(new MediaQueryDbModule(new DbQueryConfig($this->sqlDir)));
    }
}
