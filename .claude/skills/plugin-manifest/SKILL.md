---
name: plugin-manifest
description: Edits the plugin.json manifest for a Phlix plugin — entry FQCN, typed settings with defaults, event aliases, version, and phlix_min_server_version. Use when the user says 'update manifest', 'add a setting', 'subscribe to an event', 'bump plugin version', 'change the greeting default', or directly edits plugin.json. Validates that entry maps to a real class implementing the shipped contracts, settings defaults mirror in-class constants, and name keeps the phlix-plugin- prefix. Do NOT use for composer.json dependency changes (use composer tooling) or PHP source/logic edits in src/ (edit the class directly).
paths:
  - plugin.json
---
# Edit the Phlix plugin.json manifest

The manifest is the single source of truth the host's `Phlix\Plugins\PluginLoader` (in `phlix-server`) reads to instantiate, configure, and gate a plugin; it is validated against the JSON Schema bundled in `detain/phlix-shared` (`vendor/detain/phlix-shared/schemas/manifest.schema.json`). Every field is mirrored by code in `src/` — changing the manifest without changing the matching code (or vice versa) breaks the plugin at load time.

## Critical

- **`plugin.json` MUST stay valid JSON** — no trailing commas, no comments, double-quoted keys. Run `php -r 'json_decode(file_get_contents("plugin.json"), false, 512, JSON_THROW_ON_ERROR); echo "OK\n";'` after every edit. A malformed manifest makes the plugin unloadable.
- **`entry` MUST be the FQCN of a `final` class in `src/` that implements `Phlix\Shared\Plugin\LifecycleInterface`** — and, for a `metadata-provider`, `Phlix\Shared\Metadata\MetadataSourceInterface` plus `Phlix\Shared\Plugin\ConfigurableInterface` (the host silently skips settings delivery / metadata registration otherwise; `tests/ManifestContractTest.php` pins all three) — with namespace backslashes escaped as `\\` (e.g. `"Phlix\\PluginExample\\HelloMetadataProvider"`). The PSR-4 prefix in `composer.json` (`Phlix\PluginExample\` → `src/`) must resolve it.
- **A `settings` default MUST mirror the in-class fallback constant.** `settings.greeting.default` (`"Hello, World"`) matches `HelloMetadataProvider::DEFAULT_GREETING` (pinned by `testManifestGreetingDefaultMirrorsTheClassConstant` in `tests/ManifestContractTest.php`). If you change one, change the other in the same edit or the loader default and the code default diverge. The persisted value reaches the instance only through `configure(array $settings)`.
- **A secret is never a manifest default.** Declare API keys with `"secret": true` and `"default": null` (see the README **Settings & secrets** section).
- **`name` MUST start with `phlix-plugin-`** and **`version` MUST be bumped** on any behavioral manifest change (SemVer in `plugin.json`).
- This skill edits **only `plugin.json`**. Touching `composer.json` `require`, or PHP logic in `src/`, is out of scope — hand those off.

## Canonical manifest shape

```json
{
    "name": "phlix-plugin-example",
    "version": "0.1.0",
    "phlix_min_server_version": "0.10.0",
    "type": "metadata-provider",
    "entry": "Phlix\\PluginExample\\HelloMetadataProvider",
    "events": [],
    "settings": {
        "greeting": {
            "type": "string",
            "required": false,
            "default": "Hello, World",
            "label": "Greeting",
            "description": "Title this metadata source returns for the demo fixture. ..."
        }
    },
    "signature": null
}
```

A `settings` entry is an object keyed by setting name, each with `type` (the schema allows `"string"`, `"int"`/`"integer"`, `"bool"`/`"boolean"`, `"float"`, `"array"`), `required` (bool), `default` (matching `type`), optional `secret` (bool), and the optional UI copy `label`/`description`. `events` is a flat JSON array of manifest **aliases** such as `"phlix.playback.started"` (schema pattern `^phlix\.[a-z]+(?:\.[a-z_]+)+$`), each checked against `Phlix\Shared\Plugin\EventNameMap` — never class-strings; empty for metadata providers. `signature` stays `null` until the plugin is signed for distribution.

## Instructions

1. **Read `plugin.json` before editing.** Note current `version`, `entry`, and the `settings` keys. Verify it parses with the `php -r` JSON check above. Verify before proceeding to the next step.

2. **Add or change a setting.** Add a key under `settings` with `type`/`required`/`default`, e.g.:
   ```json
   "settings": {
       "greeting": { "type": "string", "required": false, "default": "Hello, World" },
       "language": { "type": "string", "required": false, "default": "en" }
   }
   ```
   Then open `src/HelloMetadataProvider.php` and confirm the matching `private const` default exists and that `configure(array $settings)` reads the key (the host delivers persisted settings only through `ConfigurableInterface::configure()`, once, before `onEnable()`). If you add a NEW setting, the entry class needs a `private const` default for the fallback and a `configure()` branch that absorbs a usable value and keeps the last good one otherwise. Verify the `default` JSON type equals the declared `type` before proceeding.

3. **Subscribe to an event.** Append the event's manifest alias to the `events` array:
   ```json
   "events": ["phlix.playback.started"]
   ```
   The alias is **validation metadata**: the host fail-fast checks it against `Phlix\Shared\Plugin\EventNameMap` (families `phlix.playback.*`, `phlix.library.*`, `phlix.user.*` — there is no `phlix.auth.*`). What actually subscribes the listener is `subscribedEvents()` in the entry class, returning `[EventClass::class => 'methodName']` keyed by the FQCN the alias maps to (`EventNameMap::fromAlias()`) — keep the two in agreement. Keep `onEnable()` cheap; do work in the listener method. Verify the alias appears in `EventNameMap::aliases()` before proceeding.

4. **Change the `entry` class.** Set `entry` to the new FQCN with `\\` escaping. Confirm a `final class` of that name exists in `src/`, `implements LifecycleInterface`, and is reachable via the `composer.json` PSR-4 map. Run `vendor/bin/phpunit` — the suite instantiates the entry class. Verify tests pass before proceeding.

5. **Bump the version.** Increment `version` in `plugin.json` using SemVer (patch for a default tweak, minor for a new setting/event, major for a breaking entry change). This is REQUIRED for any change made in steps 2–4. Uses the version you read in Step 1 as the base.

6. **Validate the whole manifest.** Run the JSON check, then `vendor/bin/phpunit`. Both must pass. If you changed a setting default, grep that the in-class constant matches: confirm `DEFAULT_GREETING` (or the relevant const) in `src/HelloMetadataProvider.php` equals the new `default`.

## Examples

**User says:** "Add a `language` setting defaulting to `en` and bump the version."

**Actions taken:**
1. Read `plugin.json` — current `version` is `0.1.0`, settings has only `greeting`.
2. Add `"language": { "type": "string", "required": false, "default": "en" }` under `settings`.
3. Add `private const DEFAULT_LANGUAGE = 'en';` to `HelloMetadataProvider` and a `configure()` branch that absorbs a usable `language` string, so the fallback mirrors the manifest and the persisted value reaches the instance.
4. Bump `version` to `0.2.0` (new setting = minor bump).
5. Run `php -r '...JSON_THROW_ON_ERROR...'` → `OK`, then `vendor/bin/phpunit` → green.

**Result:** Valid manifest with two typed settings, code default matching manifest default, version `0.2.0`.

## Common Issues

- **`vendor/bin/phpunit` fails with `Class "Phlix\PluginExample\Foo" not found` after editing `entry`:** The FQCN in `entry` has no matching class in `src/`, or backslashes weren't double-escaped. Fix: ensure `"entry"` uses `\\` and the class file exists under `src/` per the PSR-4 prefix `Phlix\PluginExample\` → `src/`. Run `composer dump-autoload` if you just added the file.
- **`Syntax error` from the `json_decode` check:** Trailing comma or comment in `plugin.json`. JSON forbids both — remove the trailing comma after the last array/object element.
- **`testManifestGreetingDefaultMirrorsTheClassConstant` fails:** The `settings.greeting.default` in the manifest no longer matches `HelloMetadataProvider::DEFAULT_GREETING`. Set both to the same string.
- **Loader rejects the plugin with a version/min-server error:** `phlix_min_server_version` is higher than the running Phlix server. Lower it to a version the host actually provides (current example: `0.10.0`), or upgrade the host.
- **Plugin name rejected at install:** `name` does not start with `phlix-plugin-`. Rename it to the `phlix-plugin-<slug>` form.
- **Install rejects an `events` entry:** the value is a class-string or an alias outside `EventNameMap` (e.g. `phlix.auth.*`). Use an alias from `EventNameMap::aliases()`.
- **Contract namespace:** the only lifecycle contract is `Phlix\Shared\Plugin\LifecycleInterface` from `detain/phlix-shared` (a hard Composer dependency); the old `Phlix\Plugins\Contract\LifecycleInterface` name and the `dev-stubs/` fallback were retired and exist nowhere in this tree.