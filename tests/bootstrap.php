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
 * The reference class implements `Phlix\Shared\Plugin\LifecycleInterface`,
 * published in `detain/phlix-shared`. That package is a hard Composer
 * dependency (see `composer.json`), so requiring the autoloader below
 * always places the genuine published contract on the classpath — the
 * suite exercises the real interface, whether run in isolation or inside
 * a host application. There is intentionally no local stub fallback.
 */

require __DIR__ . '/../vendor/autoload.php';
