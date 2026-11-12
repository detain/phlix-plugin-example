---
name: metadata-provider-class
description: Scaffolds a new Phlix lifecycle/metadata-provider class in src/ mirroring src/HelloMetadataProvider.php: declare(strict_types=1), final class, implements Phlix\Shared\Plugin\LifecycleInterface (onEnable/onDisable/subscribedEvents), constructor injection, and a path-gated lookup() that returns ['title' => ...] for a FIXTURE_PATH constant else []. Use when the user says 'add a provider', 'new plugin class', 'implement lifecycle', 'scaffold a metadata provider', or creates a new .php file under src/. Do NOT use for editing plugin.json/composer.json (manifest work) or for writing PHPUnit tests under tests/.
paths:
  - src/**/*.php
---

# Metadata Provider Class

Scaffold a new Phlix lifecycle/metadata-provider PHP class under `src/`, consistent with the shape of `src/HelloMetadataProvider.php`. The skill additionally pre-wires the one extension point the minimal demo deliberately leaves empty — it retains the host container as a property, whereas the reference class's `onEnable()` is a no-op — and emits the `@copyright`/`@license` file header the reference file carries.

## Critical

- **Live contract namespace is `Phlix\Shared\Plugin\LifecycleInterface`** (from `detain/phlix-shared`, a hard dependency in `composer.json`). The runtime class MUST import and implement this exact FQCN. The legacy `Phlix\Plugins\Contract\LifecycleInterface` name was fully retired in `355c445`; the offline `dev-stubs/` contract stub was removed once `detain/phlix-shared` became a hard dependency and resolves from the Composer autoloader — do not reintroduce either.
- **`lookup()` is didactic — the host never calls it.** There is no plugin-entry `lookup()` call site anywhere in `phlix-server/src`. `PluginLoader::enable()` registers an entry instance into the metadata `SourceRegistry` only when it is `instanceof` `Phlix\Shared\Metadata\MetadataSourceInterface`, and it delivers configured settings only through `Phlix\Shared\Plugin\ConfigurableInterface::configure(array $settings)`. A class that mirrors only this demo's `lookup()` "enables cleanly and does nothing forever" — the exact failure the README warns about. A real provider implements `MetadataSourceInterface` (`sourceName()`, `supportedMediaTypes()`, `search()`, `getDetails()`, `getImages()`) and, when it needs configured keys, `ConfigurableInterface`.
- **Every `.php` file under `src/` starts with `<?php`, a blank line, a `@copyright`/`@license` file docblock, then `declare(strict_types=1);`** — matching `src/HelloMetadataProvider.php` and the block `scripts/add-headers.php` inserts. `declare(strict_types=1);` never precedes the header docblock.
- **Namespace is `Phlix\PluginExample\`** (PSR-4 → `src/`, per `composer.json`). One `final class` per file; the file name MUST equal the class name (e.g. `class TmdbMetadataProvider` → `src/TmdbMetadataProvider.php`).
- **`onEnable()` must stay cheap** — only stash the container. Do real work in the `MetadataSourceInterface` methods (`search()`/`getDetails()`/`getImages()`) or event listeners, never in `onEnable()`.
- Reference fixtures via a class constant (e.g. `self::FIXTURE_PATH`), never a hardcoded literal inside `lookup()`.
- **API keys come from the host's secret seam, never from code.** Declare them in `plugin.json` as `"secret": true` with `"default": null` and read them only via `ConfigurableInterface::configure()` — a key literal in `src/`, a real key as a manifest default, or a settings value written raw to a log all violate the README's **"Settings & secrets"** rules (`phlix-plugin-omdb` is the reference).
- **Any real upstream call: https, explicit timeout, TLS verification on** (`Workerman\Http\Client` with `'timeout'`), and redact key-bearing URLs/errors before logging — see README **"Outbound calls"**.
- Do NOT edit `plugin.json`, `composer.json`, or write tests as part of this skill — those are separate tasks.

## Instructions

1. **Pick the class name and file path.** Class is PascalCase ending in `MetadataProvider` (e.g. `TmdbMetadataProvider`). File is `src/<ClassName>.php`. Verify no file already exists at that path before proceeding: `ls src/<ClassName>.php` must return "No such file".

2. **Write the file header.** Exactly these first lines — the `@copyright`/`@license` docblock sits between `<?php` and `declare(strict_types=1);`, matching `src/HelloMetadataProvider.php` and the block `scripts/add-headers.php` inserts:
   ```php
   <?php

   /**
    * Reference metadata-provider plugin for Phlix.
    *
    * @copyright 2026 Joe Huss <detain@interserver.net>
    * @license   MIT
    */

   declare(strict_types=1);

   namespace Phlix\PluginExample;

   use Phlix\Shared\Plugin\LifecycleInterface;
   use Psr\Container\ContainerInterface;
   ```
   Verify the namespace line reads `Phlix\PluginExample` (no trailing segment) before proceeding to Step 3.

3. **Declare the final class implementing the contract.** Uses the imports from Step 2:
   ```php
   final class <ClassName> implements LifecycleInterface
   {
   ```
   Verify the keyword is `final` and the implemented interface is the imported short name `LifecycleInterface` (resolving to `Phlix\Shared\Plugin\LifecycleInterface`).

4. **Add constants and properties** in this order — public fixture constant, private default, configurable value, nullable container:
   ```php
       public const FIXTURE_PATH = '/test/<name>.mkv';

       private const DEFAULT_GREETING = 'Hello, World';

       private string $greeting;

       private ?ContainerInterface $container = null;
   ```
   Replace `$greeting`/`DEFAULT_GREETING` with the field your provider actually returns. Keep the `?ContainerInterface $container = null;` line — it is the documented extension point.

5. **Add the constructor with injection** (override defaults for tests, fall back to the manifest default in production):
   ```php
       public function __construct(string $greeting = self::DEFAULT_GREETING)
       {
           $this->greeting = $greeting;
       }
   ```
   This uses the `DEFAULT_GREETING` constant from Step 4.

6. **Implement the three lifecycle hooks exactly.** `onEnable()` only stashes the container; `onDisable()` releases it (symmetric); `subscribedEvents()` returns `[]` for direct-invocation providers:
   ```php
       public function onEnable(ContainerInterface $container): void
       {
           $this->container = $container;
       }

       public function onDisable(): void
       {
           $this->container = null;
       }

       /**
        * @return array<class-string, string|callable>
        */
       public function subscribedEvents(): array
       {
           return [];
       }
   ```
   Verify all three signatures match `Phlix\Shared\Plugin\LifecycleInterface` from `detain/phlix-shared` (return types `void`, `void`, `array`). Only add real subscriptions if the provider listens for PSR-14 events; metadata providers normally return `[]`.

7. **Implement the path-gated `lookup()`.** Early-return `[]` for any path that is not the fixture, then return the typed payload:
   ```php
       /**
        * @return array{title: string}|array{}
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
   ```
   This uses `FIXTURE_PATH` from Step 4 and `$this->greeting` from Step 5. Remember the Critical warning above: the host never calls `lookup()`, so a real provider is **not** "the demo with a filled-in `lookup()` body". It implements `Phlix\Shared\Metadata\MetadataSourceInterface` (`search()`/`getDetails()`/`getImages()` parse the path, hit the upstream API, and return the merged array) and, for configured keys, `Phlix\Shared\Plugin\ConfigurableInterface::configure()`. When you add that wiring, follow the README's **Settings & secrets** and **Outbound calls** rules: keys arrive only through `configure()` (never literals, never manifest defaults), calls are https with an explicit timeout and TLS verification on, and no raw key ever reaches a log line or a returned string.

8. **Regenerate the autoloader and verify the class loads.** The class will not autoload until Composer's classmap knows about it:
   ```bash
   composer dump-autoload
   php -r "require 'vendor/autoload.php'; var_dump(class_exists('Phlix\\PluginExample\\<ClassName>'));"
   ```
   Expect `bool(true)`. Then run the suite to confirm nothing broke: `vendor/bin/phpunit`.

## Examples

**User says:** "Add a new metadata provider class for TMDB lookups."

**Actions taken:**

1. Chose `TmdbMetadataProvider` → `src/TmdbMetadataProvider.php`; confirmed it does not exist.
2. Wrote the header (`<?php`, then the `@copyright`/`@license` docblock, then `declare(strict_types=1);`), `namespace Phlix\PluginExample;`, and the `use` imports — `Phlix\Shared\Plugin\LifecycleInterface`, `Phlix\Shared\Metadata\MetadataSourceInterface`, `Phlix\Shared\Plugin\ConfigurableInterface`, and `Psr\Container\ContainerInterface`.
3. Declared `final class TmdbMetadataProvider implements LifecycleInterface, MetadataSourceInterface, ConfigurableInterface` — the interfaces the host actually consults, not only the lifecycle demo.
4. Added a private default and a configured-title field, a private API-key field, and `private ?ContainerInterface $container = null;` (the retained-container extension point).
5–7. Added constructor injection; the three lifecycle hooks (`onEnable()` stashes the container, `onDisable()` releases it, `subscribedEvents()` → `[]`); `configure(array $settings)` reading the API key from the secret seam; and the `MetadataSourceInterface` methods (`sourceName()`, `supportedMediaTypes()`, `search()`, `getDetails()`, `getImages()`) doing the real TMDB work over https with an explicit timeout.
8. Ran `composer dump-autoload` then `vendor/bin/phpunit`.

**Result:** `src/TmdbMetadataProvider.php` compiles, `class_exists()` returns `true`, and `vendor/bin/phpunit` stays green — and because the class satisfies `MetadataSourceInterface`, `PluginLoader::enable()` actually registers it into the metadata `SourceRegistry`. A clone that kept only the demo's `lookup()` would compile and enable but never run.

## Common Issues

- **`PHP Fatal error: Interface "Phlix\Shared\Plugin\LifecycleInterface" not found`** — `detain/phlix-shared` is not installed. Fix: `composer install` (it is pulled from the VCS repo declared in `composer.json` `repositories`). Confirm with `ls vendor/detain/phlix-shared`. Do NOT "fix" this by importing a legacy `Phlix\Plugins\Contract\LifecycleInterface` — that name exists nowhere in this tree; the error simply means the package is missing.

- **`Class "Phlix\PluginExample\<ClassName>" not found` at runtime** — the autoloader classmap is stale or the file name does not match the class name. Fix: ensure `src/<ClassName>.php` matches `class <ClassName>` exactly (case-sensitive), then run `composer dump-autoload`.

- **`Declaration of <ClassName>::subscribedEvents() must be compatible with LifecycleInterface`** — your return type or signature drifted. The three methods must be exactly `onEnable(ContainerInterface $container): void`, `onDisable(): void`, `subscribedEvents(): array`. Cross-check against `Phlix\Shared\Plugin\LifecycleInterface` in `vendor/detain/phlix-shared/src/Plugin/LifecycleInterface.php`.

- **PHPUnit can't find the class but production can (or vice-versa)** — the class is not yet on the Composer autoload classmap. Run `composer dump-autoload`; the `Phlix\Shared\Plugin\LifecycleInterface` contract itself resolves from `detain/phlix-shared` (a hard dependency) via the autoloader that `tests/bootstrap.php` requires. There is no longer a local `dev-stubs/` fallback to keep in sync.

- **`strict_types` TypeError on `lookup()`** — a caller passed a non-string `$filePath`. Keep the `string $filePath` type hint; callers in the Phlix host always pass an absolute path string. Do not loosen the hint to `mixed`.