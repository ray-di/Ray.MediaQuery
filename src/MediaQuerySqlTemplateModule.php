<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use Override;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\Annotation\SqlTemplate;

/**
 * Module for SQL template configuration.
 *
 * This module binds the SQL template string to the `SqlTemplate` annotation.
 * The default template is a simple format that includes an ID and the SQL query.
 *
 * Available words in the template:
 * - `{{ id }}`: The identifier for the SQL query.
 * - `{{ sql }}`: The SQL query string itself.
 *
 * * Example:
 *
 * "-- MyBlog: {{ id }}.sql\n{{ sql }}" // With application name
 * "-- {{ id }}.sql\n{{ sql }}" // Without application name (default)
 */
final class MediaQuerySqlTemplateModule extends AbstractModule
{
    public function __construct(
        private string $sqlTemplate = "-- {{ id }}.sql\n{{ sql }}",
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind()->annotatedWith(SqlTemplate::class)->toInstance($this->sqlTemplate);
        $this->bind()->annotatedWith(PerformSqlInterface::class)->to(PerformTemplatedSql::class);
    }
}
