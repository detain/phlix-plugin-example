<?php

/**
 * Reference metadata-provider plugin for Phlix.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */

declare(strict_types=1);

namespace Phlix\PluginExample;

use Phlix\Shared\Metadata\MetadataSourceInterface;
use Phlix\Shared\Plugin\ConfigurableInterface;
use Phlix\Shared\Plugin\LifecycleInterface;
use Psr\Container\ContainerInterface;

/**
 * Reference metadata-provider plugin for Phlix.
 *
 * The smallest plugin that demonstrates the wiring a real host actually
 * consults. The class implements the three shared contracts published
 * in `detain/phlix-shared` that matter to a metadata provider:
 *
 * - `Phlix\Shared\Plugin\LifecycleInterface` — the instantiate/enable/
 *   disable contract the loader runs every plugin through.
 * - `Phlix\Shared\Plugin\ConfigurableInterface` — the persisted-
 *   settings channel; {@see self::configure()} is how a `greeting`
 *   override from `plugin.json` reaches the instance.
 * - `Phlix\Shared\Metadata\MetadataSourceInterface` — the metadata
 *   triad {@see self::search()} → {@see self::getDetails()} /
 *   {@see self::getImages()} that the host pipeline drives, plus the
 *   identity pair {@see self::sourceName()} /
 *   {@see self::supportedMediaTypes()}.
 *
 * ## How the host consults this class
 *
 * The server-side loader `Phlix\Plugins\PluginLoader` (in
 * `detain/phlix-server`) builds the entry instance through the host
 * PSR-11 container, calls {@see self::configure()} with the persisted
 * settings, runs {@see self::onEnable()}, and — because the instance is
 * `instanceof MetadataSourceInterface` — registers it into the metadata
 * `SourceRegistry` (`Phlix\Media\Metadata\Resolution\SourceRegistry`).
 * A plugin that omits the source interface enables cleanly but is never
 * consulted by the pipeline.
 *
 * ## lookup() is didactic — the host never calls it
 *
 * {@see self::lookup()} is the demo fixture helper this class started
 * life as; no plugin-entry `lookup()` call site exists in the host.
 * Production wiring goes through the `MetadataSourceInterface` methods
 * above — {@see self::getDetails()} literally routes the fixture id
 * through this helper. Forkers replace the triad bodies with real
 * metadata lookups against TMDB, TVDB, Fanart.tv, or any other source;
 * a fork that replaces only `lookup()` enables and does nothing forever.
 *
 * ## Lifecycle notes
 *
 * - {@see onEnable()} is a no-op for the reference plugin; it accepts
 *   the host container to satisfy the contract but does not retain it.
 *   Real plugins that need a container should store it as a property.
 * - {@see subscribedEvents()} returns an empty array; this plugin does
 *   not listen for PSR-14 events. A real metadata provider can leave
 *   this empty too — `Phlix\Media\Metadata\MetadataManager` invokes
 *   providers directly rather than via the PSR-14 dispatcher.
 *
 * ## Provenance
 *
 * The contracts live in `detain/phlix-shared`:
 * `Phlix\Shared\Plugin\LifecycleInterface`,
 * `Phlix\Shared\Plugin\ConfigurableInterface`, and
 * `Phlix\Shared\Metadata\MetadataSourceInterface`.
 *
 * @link https://github.com/detain/phlix-docs/blob/master/docs/plugins/developer-guide.md Phlix plugin developer guide
 * @package Phlix\PluginExample
 * @since 0.1.0
 */
final class HelloMetadataProvider implements ConfigurableInterface, LifecycleInterface, MetadataSourceInterface
{
    /**
     * Filesystem path that {@see lookup()} treats as the canonical
     * fixture. The README documents this constant so anyone smoke-testing
     * the plugin knows what to type into the metadata picker.
     */
    public const FIXTURE_PATH = '/test/hello.mkv';

    /**
     * Canonical source name the host keys this source on — the value an
     * admin adds to a `metadata.provider_priority` list to consult it.
     * It is persisted ordering data, so it must stay stable once a
     * plugin is installed; forkers rename it to their own unique slug.
     */
    public const SOURCE_NAME = 'example';

    /**
     * External id the fixture answers to: {@see search()} returns it for
     * the query `hello`, and {@see getDetails()} routes it through the
     * {@see FIXTURE_PATH} lookup.
     */
    public const FIXTURE_ID = 'hello';

    /**
     * Default greeting used when the plugin's `greeting` setting has not
     * been overridden in `plugin.json`. Mirrors the default declared in
     * the manifest so the in-class fallback matches what the loader
     * persists at install time.
     */
    private const DEFAULT_GREETING = 'Hello, World';

    /**
     * Greeting returned as the metadata title. Defaults to
     * {@see DEFAULT_GREETING}; tests and direct instantiations may
     * override it through the constructor, and the host delivers a
     * `plugin.json` `greeting` override through {@see configure()} —
     * the settings seam this reference class implements by design.
     */
    private string $greeting;

    /**
     * @param string $greeting Override for the greeting surfaced by the
     *     fixture lookups ({@see lookup()} and, through it,
     *     {@see getDetails()}). Tests pass a custom value; the host
     *     delivers the persisted setting via {@see configure()}.
     */
    public function __construct(string $greeting = self::DEFAULT_GREETING)
    {
        $this->greeting = $greeting;
    }

    /**
     * Receive the plugin's persisted settings from the host loader.
     *
     * `PluginLoader` calls this exactly once, after the container has
     * built the instance and before {@see onEnable()} — the only channel
     * by which a `greeting` override from `plugin.json` reaches this
     * plugin. A missing key, a non-string, or an empty string leaves the
     * current greeting untouched: the manifest already declares
     * `type: string`, so this is the parse-at-the-boundary belt — absorb
     * what is usable, keep the last good value otherwise, and never
     * abort enabling over operator-config noise.
     *
     * @param array<string, mixed> $settings Raw persisted settings map,
     *     keyed by the names declared in `plugin.json`.
     *
     * @return void
     */
    public function configure(array $settings): void
    {
        $greeting = $settings['greeting'] ?? null;

        if (is_string($greeting) && $greeting !== '') {
            $this->greeting = $greeting;
        }
    }

    /**
     * Loader hook called once when the plugin is enabled.
     *
     * No-op for the reference plugin. Real plugins that need the host
     * container should store it as a property here. Settings have
     * already been delivered by {@see configure()} at this point.
     *
     * @param ContainerInterface $container Host PSR-11 container.
     *
     * @return void
     *
     * @since 0.1.0
     */
    public function onEnable(ContainerInterface $container): void
    {
    }

    /**
     * Loader hook called once when the plugin is disabled.
     *
     * No-op for the reference plugin. Real plugins that opened resources
     * in {@see onEnable()} should close them here so the lifecycle is
     * symmetric. The loader also deregisters the source from the
     * `SourceRegistry` on this path, so no artwork or priority state
     * leaks across an enable/disable cycle.
     *
     * @return void
     *
     * @since 0.1.0
     */
    public function onDisable(): void
    {
    }

    /**
     * Returns the PSR-14 listener subscriptions this plugin wants.
     *
     * Metadata providers are invoked synchronously by
     * `Phlix\Media\Metadata\MetadataManager` and therefore do not need
     * to subscribe to playback or library events. Returning an empty
     * array keeps the loader happy.
     *
     * @return array<class-string, string|callable> Always empty for the
     *     reference plugin.
     *
     * @since 0.1.0
     */
    public function subscribedEvents(): array
    {
        return [];
    }

    /**
     * The canonical, stable source name the host keys this source on.
     *
     * This is the {@see SOURCE_NAME} slug that appears in the
     * `metadata.provider_priority` lists; the admin ordering map selects
     * sources by exactly this string.
     *
     * @return non-empty-string Canonical source name.
     */
    public function sourceName(): string
    {
        return self::SOURCE_NAME;
    }

    /**
     * The media types this source can provide metadata for.
     *
     * The host registers the source under each listed media-type slug
     * and consults it only for matching items. The reference fixture
     * models a movie file; a real provider lists every type it can
     * answer for (e.g. `['anime', 'series']`).
     *
     * @return list<non-empty-string> Media-type slugs.
     */
    public function supportedMediaTypes(): array
    {
        return ['movie'];
    }

    /**
     * Search this source for items matching a free-text query.
     *
     * The host calls this first in the metadata triad; the returned
     * external ids are then fed to {@see getDetails()} and
     * {@see getImages()}. The reference source recognises exactly one
     * query — the fixture id `hello` — and ranks the fixture first;
     * every other query yields zero matches.
     *
     * @param string               $query   Free-text query (e.g. a title
     *     parsed from a filename).
     * @param array<string, mixed> $options Optional hints such as
     *     `year`/`language`; unknown keys are ignored.
     *
     * @return list<array{id: non-empty-string, title: string}> Zero or
     *     one fixture match, best-match first.
     */
    public function search(string $query, array $options = []): array
    {
        if ($query !== self::FIXTURE_ID) {
            return [];
        }

        return [
            ['id' => self::FIXTURE_ID, 'title' => $this->greeting],
        ];
    }

    /**
     * Fetch the full metadata record for an external id.
     *
     * Routes the fixture id through {@see lookup()} so the greeting
     * shape lives in exactly one place. Unknown ids return an empty
     * array — absent keys, not null-filled maps, are how "no data"
     * travels through this contract.
     *
     * @param string               $externalId External id from
     *     {@see search()}.
     * @param array<string, mixed> $options    Optional hints such as
     *     `language`; unknown keys are ignored.
     *
     * @return array<string, mixed> Detailed metadata, or `[]` when the
     *     id is unknown.
     */
    public function getDetails(string $externalId, array $options = []): array
    {
        if ($externalId !== self::FIXTURE_ID) {
            return [];
        }

        return $this->lookup(self::FIXTURE_PATH);
    }

    /**
     * Fetch image URLs for an external id, grouped by type.
     *
     * The reference source ships no artwork, so every lookup — known id
     * or not — answers with an empty array, which is the
     * contract-legal way to report "no images". A fork replaces this
     * with type groups such as `['poster' => [['url' => ...]]]`.
     *
     * @param string $externalId External id from {@see search()}.
     *
     * @return array<string, list<array{url: non-empty-string, width?: int, height?: int}>>
     *     Always empty for the reference plugin.
     */
    public function getImages(string $externalId): array
    {
        return [];
    }

    /**
     * Demonstration fixture lookup — didactic, not a host entry point.
     *
     * Returns the configured greeting as the title when called with the
     * well-known {@see FIXTURE_PATH}; returns an empty array for any
     * other input. The host never calls this method: the server drives
     * metadata through the `MetadataSourceInterface` triad above, and
     * {@see getDetails()} routes the fixture id through this helper.
     *
     * @param string $filePath Absolute filesystem path of the media
     *     item the metadata picker is asking about.
     *
     * @return array{title: string}|array{} Matched fixture payload or
     *     an empty array when the path is not recognised.
     *
     * @since 0.1.0
     */
    public function lookup(string $filePath): array
    {
        if ($filePath !== self::FIXTURE_PATH) {
            return [];
        }

        return [
            'title' => $this->greeting,
        ];
    }
}
