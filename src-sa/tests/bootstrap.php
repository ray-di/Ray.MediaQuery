<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$rootAutoload = $root . '/vendor/autoload.php';
if (is_file($rootAutoload)) {
    require_once $rootAutoload;
}

$toolsAutoload = $root . '/vendor-bin/tools/vendor/autoload.php';
if (is_file($toolsAutoload)) {
    require_once $toolsAutoload;
}

spl_autoload_register(static function (string $class) use ($root): void {
    $prefixes = [
        'Ray\\MediaQuery\\PHPStan\\Tests\\' => $root . '/src-sa/tests/',
        'Ray\\MediaQuery\\PHPStan\\' => $root . '/src-sa/src/',
        'Ray\\MediaQuery\\' => $root . '/src/',
        'Tutorial\\Blog\\' => $root . '/docs/tutorial/src/Blog/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require_once $file;
        }

        return;
    }
});
