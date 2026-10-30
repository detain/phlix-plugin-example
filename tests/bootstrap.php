<?php

/**
 * Test bootstrap for the plugin PHPUnit suite.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */

declare(strict_types=1);

/**
 * Test bootstrap for the plugin's own PHPUnit suite.
 *
 * The plugin's runtime dependency is the Phlix server, which provides
 * `Phlix\Shared\Plugin\LifecycleInterface`. In an installed plugin
 * (`var/plugins/phlix-plugin-example/`) that interface is resolved by
 * the host application's autoloader. When the plugin is tested in
 * isolation — `composer install && vendor/bin/phpunit` from this
 * repo — the host isn't on the classpath, so we declare a minimal stub
 * here that matches the published shape.
 *
 * The stub is registered only when the real interface is absent: when the
 * host application (or `detain/phlix-shared` through Composer) provides
 * `Phlix\Shared\Plugin\LifecycleInterface`, this file defers to it and the
 * suite exercises the genuine published contract. The stub exists purely so
 * a bare checkout of this repository can run its own tests.
 */

require __DIR__ . '/../vendor/autoload.php';

if (!interface_exists(\Phlix\Shared\Plugin\LifecycleInterface::class)) {
    require __DIR__ . '/../dev-stubs/LifecycleInterface.php';
}
