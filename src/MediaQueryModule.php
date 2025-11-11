<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use Ray\Di\AbstractModule;

/**
 * Low-level MediaQuery module for advanced configurations.
 *
 * This is an internal/advanced API. For most use cases, use MediaQuerySqlModule instead.
 *
 * MediaQuerySqlModule provides a simpler interface:
 * ```php
 * // Recommended: Simple and clear
 * $this->install(new MediaQuerySqlModule('/path/to/interfaces', '/path/to/sql'));
 *
 * // Advanced: When you need Queries::fromClasses() or other customization
 * $queries = Queries::fromClasses([UserInterface::class, OrderInterface::class]);
 * $this->install(new MediaQueryModule($queries, [new DbQueryConfig('/path/to/sql')]));
 * ```
 *
 * Note: The array of DbQueryConfig currently only uses the last element.
 * Multiple SQL directories may be supported in future versions if needed.
 *
 * @internal This is a low-level API. Use MediaQuerySqlModule for typical use cases.
 */
final class MediaQueryModule extends AbstractModule
{
    /** @param list<DbQueryConfig> $configs */
    public function __construct(
        private readonly Queries $queries,
        private readonly array $configs,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->install(new MediaQueryBaseModule($this->queries));
        // Note: Only the last config in the array is effective due to binding overwrite
        foreach ($this->configs as $config) {
            $this->install(new MediaQueryDbModule($config));
        }
    }
}
