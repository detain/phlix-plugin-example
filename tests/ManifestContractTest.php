<?php

/**
 * Manifest-to-contract pins for the reference plugin.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */

declare(strict_types=1);

namespace Phlix\PluginExample\Tests;

use Phlix\PluginExample\HelloMetadataProvider;
use Phlix\Shared\Metadata\MetadataSourceInterface;
use Phlix\Shared\Plugin\ConfigurableInterface;
use Phlix\Shared\Plugin\LifecycleInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionClassConstant;

/**
 * Proves the shipped `plugin.json` and the reference class agree.
 *
 * The manifest is the contract the host actually reads: `entry` names the
 * class `Phlix\Plugins\PluginLoader` instantiates, the three shared
 * interfaces decide what the loader does with that instance (lifecycle
 * hooks, settings delivery through `configure()`, registration as a
 * metadata source), and `settings.greeting.default` is what the loader
 * persists before `configure()` ever runs. None of those relationships
 * was checked anywhere before this suite, so a class rename, a dropped
 * interface, or a manifest typo shipped green. These pins are the lesson
 * the template hands every fork: the manifest is load-bearing, and it is
 * cheap to prove it in CI.
 */
final class ManifestContractTest extends TestCase
{
    private const MANIFEST_PATH = __DIR__ . '/../plugin.json';

    /**
     * Decode the shipped manifest. Every test starts from this parse, so
     * an unreadable or malformed manifest fails loudly here instead of
     * degrading into confusing per-key misses downstream.
     *
     * @return array<string, mixed> The manifest as a keyed array.
     */
    private function manifest(): array
    {
        $raw = file_get_contents(self::MANIFEST_PATH);

        $this->assertIsString(
            $raw,
            'plugin.json must sit next to composer.json — the host installs through it',
        );

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded, 'plugin.json must decode to a JSON object');

        return $decoded;
    }

    public function testManifestEntryNamesTheLoadableReferenceClass(): void
    {
        $entry = $this->manifest()['entry'] ?? null;

        $this->assertIsString($entry, 'plugin.json must carry a string "entry" FQCN');

        $this->assertTrue(
            class_exists($entry),
            "manifest entry '{$entry}' must resolve through the Composer autoloader",
        );

        $this->assertSame(
            HelloMetadataProvider::class,
            $entry,
            'the reference manifest and the reference class must name each other',
        );
    }

    public function testManifestEntryImplementsTheShippedContracts(): void
    {
        $entry = $this->manifest()['entry'] ?? null;

        $this->assertIsString($entry);

        // The use-imports above resolve from detain/phlix-shared via the
        // autoloader — since the dev-stubs fallback was retired these
        // shipped interfaces are the only definitions on the classpath,
        // so conformance here is conformance to the real contracts.
        $contracts = [
            LifecycleInterface::class,
            ConfigurableInterface::class,
            MetadataSourceInterface::class,
        ];

        foreach ($contracts as $contract) {
            $this->assertTrue(
                is_subclass_of($entry, $contract),
                "the manifest entry must implement {$contract}; the host silently"
                    . ' skips plugins that do not (no settings delivery, no'
                    . ' metadata registration)',
            );
        }
    }

    public function testManifestGreetingDefaultMirrorsTheClassConstant(): void
    {
        $default = $this->manifest()['settings']['greeting']['default'] ?? null;

        $this->assertIsString(
            $default,
            'plugin.json must declare a string default for the greeting setting',
        );

        $reflection = new ReflectionClass(HelloMetadataProvider::class);

        $this->assertTrue(
            $reflection->hasConstant('DEFAULT_GREETING'),
            'HelloMetadataProvider must keep the private DEFAULT_GREETING that the'
                . ' manifest default and the constructor fallback mirror',
        );

        $this->assertSame(
            (new ReflectionClassConstant(HelloMetadataProvider::class, 'DEFAULT_GREETING'))->getValue(),
            $default,
            'the persisted manifest default and the in-class fallback must be the'
                . ' same string, or an untouched install renders one greeting and'
                . ' a settings round-trip silently switches it',
        );
    }
}
