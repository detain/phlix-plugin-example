<?php

/**
 * Smoke tests for HelloMetadataProvider.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */

declare(strict_types=1);

namespace Phlix\PluginExample\Tests;

use Phlix\PluginExample\HelloMetadataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Smoke tests for the reference {@see HelloMetadataProvider} plugin.
 *
 * Mirrors the behavioural contract that the server-side
 * `SamplePluginSmokeTest` (in `detain/phlix`) asserts at the
 * integration boundary so the plugin can be developed in isolation
 * before it is published.
 */
final class HelloMetadataProviderTest extends TestCase
{
    public function test_returns_hello_world_for_known_fixture_path(): void
    {
        $provider = new HelloMetadataProvider();

        $result = $provider->lookup(HelloMetadataProvider::FIXTURE_PATH);

        $this->assertSame(['title' => 'Hello, World'], $result);
    }

    public function test_returns_empty_for_unknown_path(): void
    {
        $provider = new HelloMetadataProvider();

        $this->assertSame([], $provider->lookup('/library/movies/unknown.mkv'));
    }

    public function test_constructor_greeting_overrides_default(): void
    {
        $provider = new HelloMetadataProvider('Hola, Mundo');

        $this->assertSame(
            ['title' => 'Hola, Mundo'],
            $provider->lookup(HelloMetadataProvider::FIXTURE_PATH),
        );
    }

    public function test_subscribed_events_is_empty(): void
    {
        $provider = new HelloMetadataProvider();

        $this->assertSame([], $provider->subscribedEvents());
    }

    public function test_on_enable_does_not_throw(): void
    {
        $provider = new HelloMetadataProvider();
        $container = $this->createMock(ContainerInterface::class);

        // onEnable is a no-op - it should not throw any exception
        $provider->onEnable($container);

        // If we reach here, the test passed (no exception thrown)
        $this->assertTrue(true);
    }

    public function test_on_disable_does_not_throw(): void
    {
        $provider = new HelloMetadataProvider();

        // onDisable is a no-op - it should not throw any exception
        $provider->onDisable();

        // If we reach here, the test passed (no exception thrown)
        $this->assertTrue(true);
    }

    public function test_on_enable_accepts_container_and_returns_void(): void
    {
        $provider = new HelloMetadataProvider();
        $container = $this->createMock(ContainerInterface::class);

        // Capture the return value to verify it is void
        $returnValue = $provider->onEnable($container);

        $this->assertNull($returnValue);
    }

    public function test_on_disable_returns_void(): void
    {
        $provider = new HelloMetadataProvider();

        // Capture the return value to verify it is void
        $returnValue = $provider->onDisable();

        $this->assertNull($returnValue);
    }
}
