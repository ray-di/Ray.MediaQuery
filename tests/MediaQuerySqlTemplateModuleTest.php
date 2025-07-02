<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\MediaQuery\Annotation\SqlTemplate;

final class MediaQuerySqlTemplateModuleTest extends TestCase
{
    public function testDefaultSqlTemplate(): void
    {
        $module = new MediaQuerySqlTemplateModule();
        $injector = new Injector($module);

        $sqlTemplate = $injector->getInstance('', SqlTemplate::class);

        $this->assertSame("-- {{ id }}.sql\n{{ sql }}", $sqlTemplate);
    }

    public function testCustomSqlTemplate(): void
    {
        $customTemplate = "-- MyApp: {{ id }}.sql\n-- Generated SQL\n{{ sql }}";
        $module = new MediaQuerySqlTemplateModule($customTemplate);
        $injector = new Injector($module);

        $sqlTemplate = $injector->getInstance('', SqlTemplate::class);

        $this->assertSame($customTemplate, $sqlTemplate);
    }

    public function testSqlTemplateWithApplicationName(): void
    {
        $appTemplate = "-- MyBlog: {{ id }}.sql\n{{ sql }}";
        $module = new MediaQuerySqlTemplateModule($appTemplate);
        $injector = new Injector($module);

        $sqlTemplate = $injector->getInstance('', SqlTemplate::class);

        $this->assertSame($appTemplate, $sqlTemplate);
        $this->assertStringContainsString('MyBlog:', $sqlTemplate);
        $this->assertStringContainsString('{{ id }}', $sqlTemplate);
        $this->assertStringContainsString('{{ sql }}', $sqlTemplate);
    }

    public function testMinimalSqlTemplate(): void
    {
        $minimalTemplate = '{{ sql }}';
        $module = new MediaQuerySqlTemplateModule($minimalTemplate);
        $injector = new Injector($module);

        $sqlTemplate = $injector->getInstance('', SqlTemplate::class);

        $this->assertSame($minimalTemplate, $sqlTemplate);
    }

    public function testEmptySqlTemplate(): void
    {
        $emptyTemplate = '';
        $module = new MediaQuerySqlTemplateModule($emptyTemplate);
        $injector = new Injector($module);

        $sqlTemplate = $injector->getInstance('', SqlTemplate::class);

        $this->assertSame($emptyTemplate, $sqlTemplate);
    }

    public function testComplexSqlTemplate(): void
    {
        $complexTemplate = "/*\n" .
            " * Application: MyApp\n" .
            " * Query ID: {{ id }}\n" .
            " * Generated at: %datetime%\n" .
            " */\n" .
            "{{ sql }}\n" .
            "/*\n" .
            " * End of query\n" .
            ' */';

        $module = new MediaQuerySqlTemplateModule($complexTemplate);
        $injector = new Injector($module);

        $sqlTemplate = $injector->getInstance('', SqlTemplate::class);

        $this->assertSame($complexTemplate, $sqlTemplate);
        $this->assertStringContainsString('Application: MyApp', $sqlTemplate);
        $this->assertStringContainsString('{{ id }}', $sqlTemplate);
        $this->assertStringContainsString('{{ sql }}', $sqlTemplate);
    }
}
