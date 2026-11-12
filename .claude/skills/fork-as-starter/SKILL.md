---
name: fork-as-starter
description: Forks this phlix-plugin-example template into a new `phlix-plugin-` package: renames the PSR-4 prefix in composer.json, the name/entry/version in plugin.json, rewrites the class under src/, and replaces the suite under tests/, then verifies with composer dump-autoload + phpunit. Use when the user says 'fork this plugin', 'use this as a starter', 'create a new plugin from this', or 'scaffold a new phlix plugin'. Do NOT use for routine edits to the existing HelloMetadataProvider example, adding a feature to this repo in place, or bumping the version of this package.
paths:
  - composer.json
  - plugin.json
  - src/**/*.php
  - tests/**/*.php
  - scripts/**/*.php
---
# Fork as Starter

Turn this reference repo into a fresh `phlix-plugin-<slug>` package. Six files carry the old identity (`composer.json`, `plugin.json`, `src/HelloMetadataProvider.php`, `tests/HelloMetadataProviderTest.php`, `tests/ManifestContractTest.php`, `README.md`), the `Phlix\PluginExample\Tests` namespace also heads `tests/SecurityAuditCheckTest.php`, and two values are this repo's own: the `SOURCE_NAME` slug (`'example'`) and the `@copyright` line `scripts/add-headers.php` stamps. Every rename must stay consistent or the autoloader and PHPUnit break. `tests/bootstrap.php` needs no change — it only requires `vendor/autoload.php`.

## Critical

- The plugin slug MUST start with `phlix-plugin-` (e.g. `phlix-plugin-tmdb`). Enforced by convention in `plugin.json` `name`; reject any slug that does not.
- The PSR-4 namespace prefix in `composer.json`, the `namespace` line in `src/`, the `use` import + class reference in `tests/`, and the `entry` FQCN in `plugin.json` MUST all agree. A mismatch on any one yields `Class "..." not found`.
- Reset `plugin.json` `version` to `"0.1.0"` — you are starting a new package, not continuing this one. Never carry over `0.1.0`→higher history.
- Keep `composer.json` `require` (`php`, `psr/container`, and `detain/phlix-shared` at the constraint already pinned there) intact — the new plugin needs the same shared contracts. Keep the `repositories` VCS entry for `detain/phlix-shared` while that package is fetched from GitHub; drop or replace it only once you resolve it from Packagist or your own source.
- Do the work on a branch off `master`, one concern per PR (per CLAUDE.md). Never commit the rename half-done.

## Instructions

Derive two tokens first and reuse them everywhere:
- **slug** — kebab, `phlix-plugin-<x>` (composer name suffix, plugin.json name).
- **NsSuffix** — StudlyCase of `<x>` (e.g. `tmdb` → `Tmdb`), used as `Phlix\\Plugin<NsSuffix>\\`.
Confirm both tokens with the user before editing. Verify the slug starts with `phlix-plugin-` before proceeding.

### Step 1 — Rewrite `composer.json`
Edit these keys (leave `require`, `require-dev`, `repositories`, `config` untouched):
```json
"name": "detain/phlix-plugin-<x>",
"description": "<one-line description of the new provider>",
"keywords": ["phlix", "phlix-plugin", "<type>", "<x>"],
"homepage": "https://github.com/detain/phlix-plugin-<x>",
"autoload":     { "psr-4": { "Phlix\\Plugin<NsSuffix>\\": "src/" } },
"autoload-dev": { "psr-4": { "Phlix\\Plugin<NsSuffix>\\Tests\\": "tests/" } }
```
Verify: `grep -R "PluginExample" composer.json` returns nothing before the next step.

### Step 2 — Rewrite `plugin.json`
Uses NsSuffix from Step 1 for `entry`.
```json
{
    "name": "phlix-plugin-<x>",
    "version": "0.1.0",
    "phlix_min_server_version": "0.10.0",
    "type": "metadata-provider",
    "entry": "Phlix\\Plugin<NsSuffix>\\<NewClass>",
    "events": [],
    "settings": { },
    "signature": null
}
```
Keep `events: []` unless the plugin subscribes to PSR-14 events. Pick `<NewClass>` (e.g. `TmdbMetadataProvider`) — you create this file in Step 3. Verify: `name` starts with `phlix-plugin-` and `entry` matches `Phlix\Plugin<NsSuffix>\<NewClass>`.

### Step 3 — Rewrite the class under `src/`
Uses the namespace from Step 1 and `<NewClass>` from Step 2. Rename `src/HelloMetadataProvider.php` to the new class file under `src/` (`git mv`), then replace its contents following the existing shape — the same three shared contracts the reference implements, because the host consults a metadata provider only through them:
```php
<?php

/**
 * <One-line summary of the new provider>.
 *
 * @copyright <year> <Your Name> <you@example.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Phlix\Plugin<NsSuffix>;

use Phlix\Shared\Metadata\MetadataSourceInterface;
use Phlix\Shared\Plugin\ConfigurableInterface;
use Phlix\Shared\Plugin\LifecycleInterface;
use Psr\Container\ContainerInterface;

final class <NewClass> implements ConfigurableInterface, LifecycleInterface, MetadataSourceInterface
{
    /** Stable slug the host keys this source on in metadata.provider_priority. */
    public const SOURCE_NAME = '<x>';

    private ?ContainerInterface $container = null;

    /** @param array<string, mixed> $settings */
    public function configure(array $settings): void
    {
        // The ONLY channel for persisted settings/API keys: absorb usable values,
        // keep the last good value otherwise. Secrets: "secret": true, "default": null.
    }

    public function onEnable(ContainerInterface $container): void
    {
        $this->container = $container; // keep cheap — loader is in the request path
    }

    public function onDisable(): void
    {
        $this->container = null;
    }

    /** @return array<class-string, string|callable> */
    public function subscribedEvents(): array
    {
        return [];
    }

    public function sourceName(): string
    {
        return self::SOURCE_NAME;
    }

    /** @return list<non-empty-string> */
    public function supportedMediaTypes(): array
    {
        return ['movie'];
    }

    // search() → getDetails()/getImages() is the triad the host pipeline drives:
    // do the real upstream work here (https, explicit timeout, TLS verify on).
    public function search(string $query, array $options = []): array
    {
        return [];
    }

    public function getDetails(string $externalId, array $options = []): array
    {
        return [];
    }

    public function getImages(string $externalId): array
    {
        return [];
    }
}
```
Rules carried from the example: the `@copyright`/`@license` file docblock before `declare(strict_types=1);`, `final` class, one class per file, constructor injection, the shared contracts from `detain/phlix-shared` (`Phlix\Shared\Plugin\LifecycleInterface` is the only lifecycle contract — there is no other namespace). `PluginLoader::enable()` registers the entry into the metadata `SourceRegistry` only when it is `instanceof MetadataSourceInterface`, so a fork that drops it enables and does nothing. The reference's `lookup()` is a didactic helper the host never calls — drop it, or keep it only as a fixture helper behind `getDetails()`. Reference any test fixture via a `public const` on the class, never a hardcoded literal. Copy the PHPDoc `@param`/`@return` shapes from `src/HelloMetadataProvider.php` so `composer phpstan` (level 9) stays green. Verify the new file passes `php -l`.

### Step 4 — Replace the suite under `tests/`
Uses namespace + class from Steps 1–3. `git mv tests/HelloMetadataProviderTest.php` to the new test file under `tests/` and rewrite:
```php
<?php

/**
 * Tests for <NewClass>.
 *
 * @copyright <year> <Your Name> <you@example.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Phlix\Plugin<NsSuffix>\Tests;

use Phlix\Plugin<NsSuffix>\<NewClass>;
use PHPUnit\Framework\TestCase;

final class <NewClass>Test extends TestCase
{
    public function test_subscribed_events_is_empty(): void
    {
        $this->assertSame([], (new <NewClass>())->subscribedEvents());
    }
}
```
Keep `phpunit.xml` and `tests/bootstrap.php` as-is (`bootstrap="tests/bootstrap.php"`, suite dir `tests`, coverage `src`). Test method names use `snake_case` matching the existing suite (`test_returns_..._for_...`). The suite is three test files plus the bootstrap — handle the other two too:
- `tests/ManifestContractTest.php`: keep it, renaming its namespace and the `HelloMetadataProvider` references to `<NewClass>` — it pins that `plugin.json` `entry` resolves and implements all three shared contracts, and that a settings default mirrors its class constant.
- `tests/SecurityAuditCheckTest.php`: **keep it** (rename only its namespace), together with `scripts/security-audit-check.php` and the `composer-audit` job in `.github/workflows/test.yml` — that trio is the estate's whole-lock audit gate, and the guard test reddens the build if you neuter it.

Verify: `grep -R "HelloMetadataProvider\|PluginExample" tests/ src/` returns nothing.

### Step 5 — Fork chores: audit floors + file headers
There is no dev stub to align: `detain/phlix-shared` is a hard Composer dependency and `tests/bootstrap.php` only requires `vendor/autoload.php`, so the real contracts always resolve. Two chores ride with the audit gate kept in Step 4:
- **Re-pin both audit floors.** After `composer install`, re-measure your own `composer.lock` and set `MIN_AUDITED_PACKAGES` and `MIN_AUDITED_DEV_PACKAGES` in `scripts/security-audit-check.php` — the shipped values are measured on this repo's lock — and update the restated copies (`MIN_PACKAGES`/`MIN_DEV_PACKAGES` in `tests/SecurityAuditCheckTest.php`) in the same commit; `testFloorsMatchTheScript()` reddens when the two disagree.
- **Own the copyright line.** Delete `scripts/add-headers.php`, or re-parameterize its `@copyright` line with your own name before running it — as shipped it stamps this repo's author onto every file. Fix the docblocks already present in `src/`, `tests/` and `scripts/` the same way.

### Step 6 — Regenerate autoload + run tests
The PSR-4 prefix changed in Step 1, so the autoloader map is stale:
```bash
composer dump-autoload
vendor/bin/phpunit
```
Verify: phpunit reports OK with 0 failures. If `vendor/` is absent, run `composer install` first.

### Step 7 — README + repo metadata
Replace `phlix-plugin-example` / `HelloMetadataProvider` / `PluginExample` / the `example` source slug references in `README.md`. Verify: `grep -Rn "PluginExample\|phlix-plugin-example\|HelloMetadataProvider" . --include='*.php' --include='*.json' --include='*.md' --exclude-dir=vendor` is empty.

## Examples

**User says:** "Fork this plugin into a TMDB metadata provider."

**Actions taken:**
1. Tokens: slug `phlix-plugin-tmdb`, NsSuffix `Tmdb`, class `TmdbMetadataProvider`. Confirm with user.
2. `composer.json` → name `detain/phlix-plugin-tmdb`, PSR-4 `Phlix\PluginTmdb\` → `src/`, dev `Phlix\PluginTmdb\Tests\` → `tests/`.
3. `plugin.json` → name `phlix-plugin-tmdb`, version `0.1.0`, entry `Phlix\PluginTmdb\TmdbMetadataProvider`, settings adds an `api_key` string.
4. `git mv` the entry class under `src/` to the new name; rewrite namespace `Phlix\PluginTmdb`, class `final class TmdbMetadataProvider implements ConfigurableInterface, LifecycleInterface, MetadataSourceInterface`, `SOURCE_NAME = 'tmdb'`.
5. `git mv` the test file under `tests/` to match; namespace `Phlix\PluginTmdb\Tests`; repoint `ManifestContractTest` at `TmdbMetadataProvider`; rename `SecurityAuditCheckTest`'s namespace only.
6. Re-pin both audit floors (script + guard test) from the new lock; re-parameterize the `@copyright` line in `scripts/add-headers.php`.
7. `composer dump-autoload && vendor/bin/phpunit`.

**Result:** `vendor/bin/phpunit` → `OK`; `grep -Rn PluginExample . --exclude-dir=vendor` empty. New `phlix-plugin-tmdb` package on a branch off `master`, ready for real `search()`/`getDetails()` work.

## Common Issues

- **`PHP Fatal error: Interface "Phlix\Shared\Plugin\LifecycleInterface" not found`** → `detain/phlix-shared` is not installed. Run `composer install` and confirm `ls vendor/detain/phlix-shared`. Do not add a local stub or an `interface_exists` guard — the package is a hard dependency.
- **`testFloorsMatchTheScript` or the audit-floor test fails** → you re-pinned one copy of the floors but not the other, or skipped the re-measure. Fix per Step 5.
- **`Class "Phlix\Plugin<NsSuffix>\<NewClass>" not found`** in tests → you renamed `src/`/`composer.json` but skipped `composer dump-autoload`. Run it (Step 6). If still failing, `grep -R "namespace " src/ tests/` and confirm both equal the PSR-4 prefix in `composer.json`.
- **`No tests executed`** → test file class is not `final class <NewClass>Test extends TestCase` or its namespace doesn't match `autoload-dev`. Confirm `Phlix\Plugin<NsSuffix>\Tests` in both `composer.json` and the test file.
- **phpunit passes but the host can't load the plugin** → `plugin.json` `entry` FQCN doesn't match the real class, or `name` lacks the `phlix-plugin-` prefix. Re-check Step 2 against the class file from Step 3.
- **`The "detain/phlix-shared" ... could not be found`** on `composer install` → you removed the `repositories` VCS entry. Restore `{ "type": "vcs", "url": "https://github.com/detain/phlix-shared.git" }` in `composer.json`.
- **Leftover identity** → run the Step 7 grep; any hit in `*.php`/`*.json`/`*.md` (outside `vendor/`) means a rename was missed.
