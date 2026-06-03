---
name: metadata-provider-class
description: Scaffolds a new Phlix lifecycle/metadata-provider class in src/ mirroring src/HelloMetadataProvider.php: declare(strict_types=1), final class, implements Phlix\Shared\Plugin\LifecycleInterface (onEnable/onDisable/subscribedEvents), constructor injection, and a path-gated lookup() that returns ['title' => ...] for a FIXTURE_PATH constant else []. Use when the user says 'add a provider', 'new plugin class', 'implement lifecycle', 'scaffold a metadata provider', or creates a new .php file under src/. Do NOT use for editing plugin.json/composer.json (manifest work) or for writing PHPUnit tests under tests/.
---

# Metadata Provider Class

Scaffold a new Phlix lifecycle/metadata-provider PHP class under `src/`, byte-for-byte consistent with `src/HelloMetadataProvider.php`.

## Critical

- **Live contract namespace is `Phlix\Shared\Plugin\LifecycleInterface`** (from `detain/phlix-shared ^0.6`). The runtime class MUST import and implement this exact FQCN. Do NOT use the legacy `Phlix\Plugins\Contract\LifecycleInterface` in `src/` — that name appears only in `tests/bootstrap.php` and `dev-stubs/LifecycleInterface.php` and is known drift.
- **Every `.php` file under `src/` starts with `<?php`, a blank line, then `declare(strict_types=1);`** — no exceptions.
- **Namespace is `Phlix\PluginExample\`** (PSR-4 → `src/`, per `composer.json`). One `final class` per file; the file name MUST equal the class name (e.g. `class TmdbMetadataProvider` → `src/TmdbMetadataProvider.php`).
- **`onEnable()` must stay cheap** — only stash the container. Do real work in `lookup()` or event listeners, never in `onEnable()`.
- Reference fixtures via a class constant (e.g. `self::FIXTURE_PATH`), never a hardcoded literal inside `lookup()`.
- Do NOT edit `plugin.json`, `composer.json`, or write tests as part of this skill — those are separate tasks.

## Instructions

1. **Pick the class name and file path.** Class is PascalCase ending in `MetadataProvider` (e.g. `TmdbMetadataProvider`). File is `src/<ClassName>.php`. Verify no file already exists at that path before proceeding: `ls src/<ClassName>.php` must return "No such file".

2. **Write the file header.** Exactly these first lines:
   ```php
   <?php

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
   Verify all three signatures match the interface in `dev-stubs/LifecycleInterface.php` (return types `void`, `void`, `array`). Only add real subscriptions if the provider listens for PSR-14 events; metadata providers normally return `[]`.

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
   This uses `FIXTURE_PATH` from Step 4 and `$this->greeting` from Step 5. A real provider replaces the body with parse-path → call upstream API → return the merged array.

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
2. Wrote header with `declare(strict_types=1);`, `namespace Phlix\PluginExample;`, and the two `use` imports (`Phlix\Shared\Plugin\LifecycleInterface`, `Psr\Container\ContainerInterface`).
3. Declared `final class TmdbMetadataProvider implements LifecycleInterface`.
4. Added `public const FIXTURE_PATH = '/test/tmdb.mkv';`, a private default, `private string $title;`, and `private ?ContainerInterface $container = null;`.
5–7. Added constructor injection, the three lifecycle hooks (`subscribedEvents()` → `[]`), and a path-gated `lookup()` returning `['title' => $this->title]`.
8. Ran `composer dump-autoload` then `vendor/bin/phpunit`.

**Result:** `src/TmdbMetadataProvider.php` compiles, `class_exists()` returns `true`, and `vendor/bin/phpunit` stays green — the new class is structurally identical to `HelloMetadataProvider`, ready for real TMDB logic in `lookup()`.

## Common Issues

- **`PHP Fatal error: Interface "Phlix\Shared\Plugin\LifecycleInterface" not found`** — `detain/phlix-shared` is not installed. Fix: `composer install` (it is pulled from the VCS repo declared in `composer.json` `repositories`). Confirm with `ls vendor/detain/phlix-shared`. Do NOT "fix" this by switching the `src/` import to the legacy `Phlix\Plugins\Contract\LifecycleInterface` — that stub is for tests only.

- **`Class "Phlix\PluginExample\<ClassName>" not found` at runtime** — the autoloader classmap is stale or the file name does not match the class name. Fix: ensure `src/<ClassName>.php` matches `class <ClassName>` exactly (case-sensitive), then run `composer dump-autoload`.

- **`Declaration of <ClassName>::subscribedEvents() must be compatible with LifecycleInterface`** — your return type or signature drifted. The three methods must be exactly `onEnable(ContainerInterface $container): void`, `onDisable(): void`, `subscribedEvents(): array`. Cross-check against `dev-stubs/LifecycleInterface.php`.

- **PHPUnit can't find the class but production can (or vice-versa)** — `tests/bootstrap.php` only registers the dev stub when `Phlix\Plugins\Contract\LifecycleInterface` is absent, and that legacy namespace differs from the live `Phlix\Shared\Plugin\LifecycleInterface`. If you change the contract, keep `dev-stubs/LifecycleInterface.php` and `tests/bootstrap.php` aligned (known drift documented in CLAUDE.md).

- **`strict_types` TypeError on `lookup()`** — a caller passed a non-string `$filePath`. Keep the `string $filePath` type hint; callers in the Phlix host always pass an absolute path string. Do not loosen the hint to `mixed`.