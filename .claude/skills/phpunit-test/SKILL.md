---
name: phpunit-test
description: Adds a PHPUnit ^12.5 test under tests/ mirroring tests/HelloMetadataProviderTest.php for the phlix-plugin-example project: a final TestCase in namespace Phlix\PluginExample\Tests\, test_snake_case methods, assertSame() assertions, and fixtures referenced via HelloMetadataProvider::FIXTURE_PATH. Use when the user says 'write a test', 'add a unit test', 'test this class/method', or runs vendor/bin/phpunit. Do NOT use for non-PHP tests, JS tests, editing phpunit.xml setup, or changing tests/bootstrap.php.
paths:
  - tests/**/*.php
---
# PHPUnit Test (phlix-plugin-example)

Adds a PHPUnit `^12.5` test file under `tests/` that is byte-for-byte consistent with the existing `tests/HelloMetadataProviderTest.php`. One `final` TestCase per class under test.

## Critical

- **Every test file starts with `<?php`, a blank line, the `@copyright`/`@license` file docblock, then `declare(strict_types=1);`** — the header every file under `tests/` carries and `scripts/add-headers.php` inserts. No exceptions.
- **Test namespace is `Phlix\PluginExample\Tests\`** (PSR-4 → `tests/`, see `composer.json` `autoload-dev`). The class under test lives in `Phlix\PluginExample\` (→ `src/`).
- **Use `assertSame()`, not `assertEquals()`.** Value assertions in the suite are `assertSame()` for exact type-strict comparison (`assertNull()` only for the void lifecycle hooks, `assertCount()` only for a size check).
- **Test classes are `final`** and extend `PHPUnit\Framework\TestCase`.
- **Test methods use `test_snake_case` naming** (e.g. `test_returns_hello_world_for_known_fixture_path`), return `: void`, and take no arguments unless fed by a `#[DataProvider]` attribute (the `configure()` absorb cases use one).
- **Reference fixtures via the class constant** `HelloMetadataProvider::FIXTURE_PATH` — NEVER hardcode the fixture path. For deliberately-unknown paths an inline literal is fine.
- **Do NOT edit `phpunit.xml` or `tests/bootstrap.php`.** The bootstrap only requires `vendor/autoload.php` — `detain/phlix-shared` is a hard Composer dependency, so the real `Phlix\Shared\*` contracts always resolve and there is no `dev-stubs/` fallback. Touching either is out of scope for this skill.
- **`tests/SecurityAuditCheckTest.php` and `tests/ManifestContractTest.php` are guard tests, not templates.** Both use camelCase `testDescription()` names by design; plugin tests keep the `test_snake_case` style below.

## Instructions

1. **Identify the class under test.** It must already exist in `src/` (e.g. `src/HelloMetadataProvider.php`). Read it to learn its public methods, constructor signature, constants, and return shapes. Verify the class is `final` and its FQCN before proceeding.

2. **Create the test file** under `tests/` (one test class per source class, mirroring `tests/HelloMetadataProviderTest.php`). Use this exact header skeleton:
   ```php
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

   final class HelloMetadataProviderTest extends TestCase
   {
   }
   ```
   Verify the filename matches the test class name and the `use` import points at the real `src/` class (from Step 1) before adding methods.

3. **Add one `test_snake_case` method per behavior.** Instantiate the subject locally inside each method (no shared `setUp()` unless a fixture is reused across 3+ methods). Each method follows arrange → act → assert with `assertSame(expected, actual)`:
   ```php
   public function test_returns_hello_world_for_known_fixture_path(): void
   {
       $subject = new HelloMetadataProvider();

       $result = $subject->lookup(HelloMetadataProvider::FIXTURE_PATH);

       $this->assertSame(['title' => 'Hello, World'], $result);
   }
   ```
   This step uses the method/return-shape facts gathered in Step 1. For multi-line `assertSame` calls, keep a trailing comma on the last argument (matches `test_constructor_greeting_overrides_default`). Verify each assertion uses `assertSame` and references `FIXTURE_PATH` (not a literal) for the known-fixture case.

4. **Cover the contract surface.** Mirror the reference test: the `MetadataSourceInterface` triad (`search()` fixture-only/exact-match/options ignored, `getDetails()` known and unknown id, the `search()` → `getDetails()` id hand-off), the `ConfigurableInterface::configure()` absorb rule (a usable string overrides; missing, empty and non-string values keep the last good value — a `#[DataProvider]` keeps those cases compact), the lifecycle hooks (`onEnable()`/`onDisable()` return `null`, `subscribedEvents()` → `[]`), plus the didactic `lookup()` happy path, unknown paths and constructor override. The reference suite does not yet pin `sourceName()`, `supportedMediaTypes()` or `getImages()`; a new class should. Verify you have a test for each public method of the class from Step 1.

5. **Run the suite and confirm green** before reporting done:
   ```bash
   vendor/bin/phpunit --filter HelloMetadataProviderTest
   vendor/bin/phpunit            # full suite, must stay green
   ```
   Do not claim the test passes without pasting the `OK (N tests, M assertions)` line. If `vendor/` is missing, run `composer install` first.

## Examples

**User says:** "Add a test that the greeting override works."

**Actions taken:**
1. Read `src/HelloMetadataProvider.php` → constructor is `__construct(string $greeting = self::DEFAULT_GREETING)`; `lookup()` returns `['title' => $greeting]` for `FIXTURE_PATH`.
2. Open existing `tests/HelloMetadataProviderTest.php` (the target file already exists).
3. Add method:
   ```php
   public function test_constructor_greeting_overrides_default(): void
   {
       $provider = new HelloMetadataProvider('Hola, Mundo');

       $this->assertSame(
           ['title' => 'Hola, Mundo'],
           $provider->lookup(HelloMetadataProvider::FIXTURE_PATH),
       );
   }
   ```
4. Run `vendor/bin/phpunit --filter test_constructor_greeting_overrides_default`.

**Result:** `OK (1 test, 1 assertion)`; method matches the existing file's style exactly — `final` class, snake_case name, `assertSame`, `FIXTURE_PATH` constant, trailing comma on the multi-line assert.

## Common Issues

- **`Class "Phlix\PluginExample\HelloMetadataProvider" not found`**: The source class isn't on the classpath. 1. Confirm the file exists under `src/` with the matching FQCN. 2. Run `composer dump-autoload`. 3. Check `composer.json` `autoload.psr-4` maps `Phlix\PluginExample\` → `src/`.

- **`Error: No code coverage driver available` / coverage warnings**: Harmless for plain test runs (`phpunit.xml` declares a `<source>` block). To silence, run `vendor/bin/phpunit --no-coverage`, or install `pcov`/`xdebug` if you actually need coverage.

- **`Interface "Phlix\Shared\Plugin\LifecycleInterface" not found`** when running tests: `detain/phlix-shared` didn't install. 1. Run `composer install` (it resolves the VCS repo declared in `composer.json` `repositories`). 2. Confirm with `ls vendor/detain/phlix-shared`. There is no local stub fallback — the class needs the real `detain/phlix-shared` package at the constraint pinned in `composer.json`. Do not add a stub or an `interface_exists` guard to paper over a failed install.

- **`Failed asserting that two arrays are identical`**: `assertSame` is order- and type-strict. 1. Confirm the expected array key order matches the source return (`['title' => ...]`). 2. Ensure you didn't use `==`-style expectations — values like `'1'` vs `1` will differ. 3. Re-read the method's `@return` shape in `src/`.

- **`Test method ... is not annotated/recognized`**: PHPUnit 12 discovers methods prefixed with `test`. Ensure the method name starts with `test_` and is `public ... : void` with no params unless a `#[DataProvider]` feeds it (this project does not use `#[Test]` attributes or `@test` docblocks).
