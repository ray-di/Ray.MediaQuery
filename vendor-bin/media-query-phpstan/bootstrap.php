<?php

declare(strict_types=1);

/*
 * Test bootstrap for the self-contained PHPStan extension package.
 *
 * Loads this package's own vendor (phpstan + phpunit + this package), then the
 * parent ray/media-query autoloader so fixtures can reference the real
 * annotation / result / Pages types instead of stubs.
 */

require __DIR__ . '/vendor/autoload.php';

$parentAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (is_file($parentAutoload)) {
    require $parentAutoload;
}
