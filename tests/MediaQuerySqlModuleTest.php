<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

class MediaQuerySqlModuleTest extends TestCase
{
    public function testModuleCanBeInstalled(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new MediaQuerySqlModule(
                    __DIR__ . '/Fake/Queries',
                    __DIR__ . '/sql',
                ));
                $this->install(new AuraSqlModule('sqlite::memory:'));
            }
        };

        $injector = new Injector($module);
        // Test that basic services are bound
        $logger = $injector->getInstance(MediaQueryLoggerInterface::class);
        $this->assertInstanceOf(MediaQueryLoggerInterface::class, $logger);
    }

    public function testSimplifiedApiComparedToOldWay(): void
    {
        // New simplified API
        $newModule = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new MediaQuerySqlModule(
                    __DIR__ . '/Fake/Queries',
                    __DIR__ . '/sql',
                ));
                $this->install(new AuraSqlModule('sqlite::memory:'));
            }
        };

        // Old verbose API (deprecated)
        $oldModule = new class extends AbstractModule {
            protected function configure(): void
            {
                $queries = Queries::fromDir(__DIR__ . '/Fake/Queries');
                $this->install(new MediaQueryModule($queries, [new DbQueryConfig(__DIR__ . '/sql')]));
                $this->install(new AuraSqlModule('sqlite::memory:'));
            }
        };

        // Both should bind the same basic services
        $newInjector = new Injector($newModule);
        $oldInjector = new Injector($oldModule);

        $newLogger = $newInjector->getInstance(MediaQueryLoggerInterface::class);
        $oldLogger = $oldInjector->getInstance(MediaQueryLoggerInterface::class);

        $this->assertInstanceOf(MediaQueryLoggerInterface::class, $newLogger);
        $this->assertInstanceOf(MediaQueryLoggerInterface::class, $oldLogger);
    }
}
