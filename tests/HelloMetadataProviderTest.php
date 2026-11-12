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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Smoke tests for the reference {@see HelloMetadataProvider} plugin.
 *
 * Exercises the plugin's own contract in isolation — the settings seam
 * ({@see HelloMetadataProvider::configure()}), the host-driven metadata
 * pair ({@see HelloMetadataProvider::search()} →
 * {@see HelloMetadataProvider::getDetails()}), the didactic lookup helper,
 * empty event bindings and lifecycle no-ops — so the template can be
 * verified with `composer install && vendor/bin/phpunit`, with or without
 * the host application on the classpath.
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

    public function test_readme_in_process_demo_holds(): void
    {
        // Mirrors the README "Use" snippet line for line: the host's
        // settings channel, then the production entry point, then the
        // didactic helper. If this test changes, the README must too.
        $provider = new HelloMetadataProvider();
        $provider->configure(['greeting' => 'Bonjour, Monde']);

        $this->assertSame(['title' => 'Bonjour, Monde'], $provider->getDetails('hello'));
        $this->assertSame(['title' => 'Bonjour, Monde'], $provider->lookup('/test/hello.mkv'));
        $this->assertSame([], $provider->lookup('/anything/else'));
    }

    public function test_configure_applies_string_greeting_to_search_and_get_details(): void
    {
        $provider = new HelloMetadataProvider();

        $provider->configure(['greeting' => 'Hallo, Welt']);

        $this->assertSame(
            [['id' => HelloMetadataProvider::FIXTURE_ID, 'title' => 'Hallo, Welt']],
            $provider->search(HelloMetadataProvider::FIXTURE_ID),
        );
        $this->assertSame(
            ['title' => 'Hallo, Welt'],
            $provider->getDetails(HelloMetadataProvider::FIXTURE_ID),
        );
    }

    public function test_configure_overrides_constructor_greeting(): void
    {
        $provider = new HelloMetadataProvider('Hola, Mundo');

        $provider->configure(['greeting' => 'Ciao, Mondo']);

        $this->assertSame(['title' => 'Ciao, Mondo'], $provider->getDetails('hello'));
    }

    public function test_configure_ignores_unrelated_settings_keys(): void
    {
        $provider = new HelloMetadataProvider();

        $provider->configure(['api_key' => 'not-a-greeting', 'timeout' => 5]);

        $this->assertSame(['title' => 'Hello, World'], $provider->getDetails('hello'));
    }

    /**
     * Settings maps the boundary must absorb without touching the greeting:
     * a missing key, an empty string, and every non-string shape a
     * hand-edited or corrupted settings row could carry.
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function unusableGreetingSettings(): array
    {
        return [
            'empty settings map' => [[]],
            'empty string' => [['greeting' => '']],
            'null' => [['greeting' => null]],
            'integer' => [['greeting' => 42]],
            'float' => [['greeting' => 1.5]],
            'boolean true' => [['greeting' => true]],
            'boolean false' => [['greeting' => false]],
            'list of strings' => [['greeting' => ['Bonjour']]],
            'stringable object' => [['greeting' => new class {
                public function __toString(): string
                {
                    return 'Bonjour';
                }
            }]],
        ];
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('unusableGreetingSettings')]
    public function test_configure_keeps_default_greeting_for_unusable_value(array $settings): void
    {
        $provider = new HelloMetadataProvider();

        $provider->configure($settings);

        $this->assertSame(['title' => 'Hello, World'], $provider->getDetails('hello'));
        $this->assertSame(
            [['id' => 'hello', 'title' => 'Hello, World']],
            $provider->search('hello'),
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('unusableGreetingSettings')]
    public function test_configure_keeps_last_good_greeting_for_unusable_value(array $settings): void
    {
        $provider = new HelloMetadataProvider();
        $provider->configure(['greeting' => 'Bonjour, Monde']);

        $provider->configure($settings);

        $this->assertSame(['title' => 'Bonjour, Monde'], $provider->getDetails('hello'));
    }

    public function test_configure_accepts_whitespace_only_greeting_verbatim(): void
    {
        // The absorb rule rejects only the empty string; anything else that
        // is a string is the operator's choice and passes through untrimmed.
        $provider = new HelloMetadataProvider();

        $provider->configure(['greeting' => ' ']);

        $this->assertSame(['title' => ' '], $provider->getDetails('hello'));
    }

    public function test_search_returns_fixture_match_for_fixture_id(): void
    {
        $provider = new HelloMetadataProvider();

        $this->assertSame(
            [['id' => 'hello', 'title' => 'Hello, World']],
            $provider->search(HelloMetadataProvider::FIXTURE_ID),
        );
    }

    public function test_search_ignores_options(): void
    {
        $provider = new HelloMetadataProvider();

        $this->assertSame(
            [['id' => 'hello', 'title' => 'Hello, World']],
            $provider->search('hello', ['year' => 2026, 'language' => 'fr', 'unknown' => true]),
        );
        $this->assertSame([], $provider->search('goodbye', ['year' => 2026]));
    }

    public function test_search_returns_empty_for_non_fixture_queries(): void
    {
        $provider = new HelloMetadataProvider();

        $this->assertSame([], $provider->search(''));
        $this->assertSame([], $provider->search('goodbye'));
        $this->assertSame([], $provider->search('Hello'));
        $this->assertSame([], $provider->search('HELLO'));
        $this->assertSame([], $provider->search(' hello'));
        $this->assertSame([], $provider->search('hello '));
        $this->assertSame([], $provider->search('hello world'));
        $this->assertSame([], $provider->search(HelloMetadataProvider::FIXTURE_PATH));
    }

    public function test_get_details_routes_fixture_id_through_lookup(): void
    {
        $provider = new HelloMetadataProvider('Hej, Verden');

        $this->assertSame(
            $provider->lookup(HelloMetadataProvider::FIXTURE_PATH),
            $provider->getDetails(HelloMetadataProvider::FIXTURE_ID),
        );
        $this->assertSame(['title' => 'Hej, Verden'], $provider->getDetails('hello', ['language' => 'da']));
    }

    public function test_get_details_returns_empty_for_unknown_ids(): void
    {
        $provider = new HelloMetadataProvider();

        $this->assertSame([], $provider->getDetails(''));
        $this->assertSame([], $provider->getDetails('goodbye'));
        $this->assertSame([], $provider->getDetails('Hello'));
        $this->assertSame([], $provider->getDetails('hello '));
        // The id and the path are separate key spaces: a path is not an id.
        $this->assertSame([], $provider->getDetails(HelloMetadataProvider::FIXTURE_PATH));
    }

    public function test_search_result_id_feeds_get_details(): void
    {
        // The host's triad order: every id search() hands back must resolve
        // through getDetails() to the same title search() advertised.
        $provider = new HelloMetadataProvider();
        $provider->configure(['greeting' => 'Bonjour, Monde']);

        $matches = $provider->search('hello');

        $this->assertCount(1, $matches);
        foreach ($matches as $match) {
            $this->assertSame(['title' => $match['title']], $provider->getDetails($match['id']));
        }
    }
}
