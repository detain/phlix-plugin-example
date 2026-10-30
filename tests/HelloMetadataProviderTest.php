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
 * Exercises the plugin's own contract in isolation — lookup results,
 * empty event bindings, lifecycle no-ops — so the template can be verified
 * with `composer install && vendor/bin/phpunit`, with or without the host
 * application on the classpath.
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

    public function test_lookup_with_similar_path_returns_empty(): void
    {
        $provider = new HelloMetadataProvider();

        // Paths that are similar but not exact matches
        $this->assertSame([], $provider->lookup('/test/hello.mkv1'));
        $this->assertSame([], $provider->lookup('/test/hello.mk'));
        $this->assertSame([], $provider->lookup('/test/hello.mkv '));
        $this->assertSame([], $provider->lookup(' /test/hello.mkv'));
        $this->assertSame([], $provider->lookup('/test/hello'));
    }

    public function test_lookup_with_empty_string_returns_empty(): void
    {
        $provider = new HelloMetadataProvider();

        $this->assertSame([], $provider->lookup(''));
    }

    public function test_lookup_is_case_sensitive(): void
    {
        $provider = new HelloMetadataProvider();

        // Exact case match should work
        $this->assertSame(['title' => 'Hello, World'], $provider->lookup('/test/hello.mkv'));
        // Different case should not match
        $this->assertSame([], $provider->lookup('/TEST/HELLO.MKV'));
        $this->assertSame([], $provider->lookup('/Test/Hello.mkv'));
    }

    public function test_fixture_path_constant_has_expected_value(): void
    {
        $this->assertSame('/test/hello.mkv', HelloMetadataProvider::FIXTURE_PATH);
    }

    public function test_lookup_with_root_path_does_not_match(): void
    {
        $provider = new HelloMetadataProvider();

        // The fixture path is specifically /test/hello.mkv
        // Root level paths should not match
        $this->assertSame([], $provider->lookup('/hello.mkv'));
        $this->assertSame([], $provider->lookup('/test'));
    }

    public function test_lookup_with_null_bytes_in_path_returns_empty(): void
    {
        $provider = new HelloMetadataProvider();

        // Null bytes in path should result in no match
        $this->assertSame([], $provider->lookup("/test/hello.mkv\0"));
        $this->assertSame([], $provider->lookup("\0/test/hello.mkv"));
    }
}
