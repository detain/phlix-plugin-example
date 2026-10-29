# phlix-plugin-example

[![tests](https://github.com/detain/phlix-plugin-example/actions/workflows/test.yml/badge.svg)](https://github.com/detain/phlix-plugin-example/actions/workflows/test.yml)

> Reference **metadata-provider** plugin for [Phlix](https://github.com/detain/phlix-server)
> — the smallest plugin that exercises the full Phase A loader lifecycle.

This repo is the canonical hello-world template for Phlix plugin
authors. It does almost nothing on purpose: it implements the
`Phlix\Shared\Plugin\LifecycleInterface` contract, returns a fixed
greeting when its `lookup()` method is asked about one well-known
fixture path, and ships with a CI workflow plus PHPUnit tests so you
can fork it as a starter. Read **"What it does"** below before you copy
anything — the demo method is deliberately *not* wired to a live host
call path, and the live call paths are named there.

## What it does

Calling `HelloMetadataProvider::lookup('/test/hello.mkv')` returns:

```php
['title' => 'Hello, World']
```

Any other path returns an empty array.

> **Read this before you copy the class.** `lookup()` is a didactic
> method: the host never calls it. There is no plugin-entry `lookup()`
> call site anywhere in `phlix-server/src`, and the loader only
> requires `LifecycleInterface` to enable a plugin — so a clone that
> keeps this shape enables cleanly and does nothing forever. The
> contracts the host actually consults are:
>
> - **Metadata sources** — implement
>   `Phlix\Shared\Metadata\MetadataSourceInterface`
>   (`sourceName()`, `supportedMediaTypes()`, `search()`,
>   `getDetails()`, `getImages()`). `PluginLoader::enable()` registers
>   an entry instance into the metadata `SourceRegistry` **only** when
>   it is `instanceof` that interface.
> - **Settings delivery** — implement
>   `Phlix\Shared\Plugin\ConfigurableInterface`
>   (`configure(array $settings)`). Per the loader's own words this is
>   "the ONLY channel by which a plugin receives its configured API
>   keys/options".
>
> This reference class implements neither, which is why the
> `greeting` setting is stored but never delivered: an operator's
> override round-trips into the `plugins` table while the instance
> keeps its constructor default. The live metadata plugins (e.g.
> `phlix-plugin-omdb`) show the real arms; the
> [developer guide](https://github.com/detain/phlix-docs/blob/master/docs/plugins/developer-guide.md)
> tours the contracts.

## Install

The plugin is unsigned by design (`signature: null`) — the host logs a
warning and proceeds, because the trusted-key allowlist ships empty.
Install into a running Phlix server:

1. Log in as an admin user (`users.is_admin = 1`).
2. Open the admin **Plugins** section at `/app/admin/plugins` (the
   legacy `/admin/plugins` path redirects there).
3. Use the **Install from URL** panel. The installer accepts three
   flavours: a repository URL (rewritten to its default-branch
   tarball by `SourceUrlResolver`), a direct archive URL
   (`*.zip`, `*.tar.gz`, `*.tgz`), or a **stub** `plugin.json` whose
   `source` field points at such an archive.

   ⚠ **A GitHub URL paste does not complete on a current server —
   verified 2026-10-29 against `detain/phlix-plugin-example` at every
   shape.** Every `https://github.com/OWNER/REPO/archive/…` URL
   answers `302`, and the installer refuses any redirect before
   extraction (R20-02 SSRF gate): the paste fails with
   `HTTP redirects are not allowed (received HTTP/1.1 302 Found)`.
   The recipe this README used to print — paste the
   `raw.githubusercontent.com/.../main/plugin.json` URL — fails twice
   over: the branch is `master` (that URL 404s), and even corrected,
   this manifest has no `source` field, so the stub arm throws
   `Stub plugin.json must contain a "source" field pointing at a
   tarball or zip.` A pinned, digest-verified remote fetch is
   catalog-driven; the server-side hop that would let a GitHub paste
   reach `codeload` directly is not shipped yet. What works today:

   - **Local archive (the procedure verified in this fix's walk):**
     from one directory above your checkout, package it (a single
     root directory is flattened automatically):

     ```bash
     tar czf /tmp/phlix-plugin-example.tar.gz \
         --exclude='*/vendor' --exclude='*/.git' phlix-plugin-example
     ```

     paste `file:///tmp/phlix-plugin-example.tar.gz`, or run the same
     install from the server's CLI (from the `phlix-server` install
     directory):

     ```bash
     php bin/phlix plugin:install file:///tmp/phlix-plugin-example.tar.gz
     ```

     `file://` sources are exempt from both the HTTPS-only gate and
     the pin requirement below — they are operator-local bytes.
   - **A remote archive that answers `200` with no redirect** (e.g.
     a self-hosted artifact download ending in `.tar.gz`/`.zip`), or a
     stub `plugin.json` whose `source` names one. Remote, un-pinned
     sources are refused by the loader's default-deny (SV-S2b):
     `Refusing to install unverified plugin source …` — unless the
     source matches a catalog entry carrying `ref` + `artifactSha256`
     (this repo is listed in the official
     [`detain/phlix-plugins`](https://github.com/detain/phlix-plugins)
     catalog), or the operator sets `PHLIX_PLUGINS_ALLOW_UNVERIFIED=1`
     as an escape hatch (logs a warning). A catalog *install* click
     still resolves to a `github.com/.../archive/...` fetch, so the
     curated path hits the same redirect wall until a server-side hop
     ships. HTTPS-only applies unless `PHLIX_PLUGINS_ALLOW_HTTP=1`
     (the admin endpoint stays HTTPS/`file://`-only regardless).

4. The server downloads (or copies) the archive, validates the
   manifest with `Phlix\Plugins\Manifest\ManifestSchema` against the
   JSON Schema bundled in
   [`detain/phlix-shared`](https://github.com/detain/phlix-shared/blob/master/schemas/manifest.schema.json),
   checks `phlix_min_server_version`, verifies any signature, runs
   `composer install --no-dev` (with `--no-scripts --no-plugins` — the
   host's RCE kill-switch for install hooks), and stores a row in the
   `plugins` table. The plugin lands **disabled** by default.
5. Flip the toggle in the table to enable it.

The same operations are reachable via the JSON API —
`POST /api/v1/admin/plugins/install` with body `{"url": "..."}`, then
`POST /api/v1/admin/plugins/{name}/enable`; see
[`docs/plugins/install-from-url.md`](https://github.com/detain/phlix-docs/blob/master/docs/plugins/install-from-url.md)
for the `curl` recipes, and
[`docs/plugins/install-from-catalog.md`](https://github.com/detain/phlix-docs/blob/master/docs/plugins/install-from-catalog.md)
for the curated one-click path.

## Use

Because the host never calls `lookup()` (see "What it does"), the only
honest demo is the in-process one — exactly what
`tests/HelloMetadataProviderTest.php` exercises:

```php
$provider = new \Phlix\PluginExample\HelloMetadataProvider();
$provider->lookup('/test/hello.mkv'); // ['title' => 'Hello, World']
$provider->lookup('/anything/else');  // []
```

The fixture path lives at
`Phlix\PluginExample\HelloMetadataProvider::FIXTURE_PATH` if you want
to reference it from tests.

## Fork as a starter

This repository is intentionally small (one PHP class, two test files
plus a bootstrap) so you can copy it as the seed for your own plugin:

1. **Fork** or `git clone` this repo, then rename the new directory.
2. Edit **`plugin.json`** — pick a new `name` (must start with
   `phlix-plugin-`), set `version`, change `entry` to your FQCN, and
   keep `phlix_min_server_version` at what your code actually
   requires. The `events` aliases you may declare there are
   **validation metadata**: the host fail-fast checks each against
   `Phlix\Shared\Plugin\EventNameMap`'s real families
   (`phlix.playback.*`, `phlix.library.*`, `phlix.user.*` — there is
   no `phlix.auth.*` family); what actually subscribes your listeners
   is your entry class's `subscribedEvents()`. The manifest schema is
   the JSON Schema bundled in
   [`detain/phlix-shared/schemas/manifest.schema.json`](https://github.com/detain/phlix-shared/blob/master/schemas/manifest.schema.json);
   the key-by-key guide is
   [`docs/plugins/manifest.md`](https://github.com/detain/phlix-docs/blob/master/docs/plugins/manifest.md).

   > **Note:** If you declare a setting `required:true`, give it a `default`
   > or the host will null-fill it — see
   > [`docs/plugins/manifest.md`](https://github.com/detain/phlix-docs/blob/master/docs/plugins/manifest.md).
   > Settings only ever reach your instance through
   > `ConfigurableInterface::configure()`.
3. Edit **`composer.json`** — rename the package, change the PSR-4
   prefix under `autoload.psr-4`.
4. Rewrite **`src/HelloMetadataProvider.php`** with your own
   implementation, keeping `onEnable()` cheap and doing the heavy work
   in the listener methods declared by `subscribedEvents()`. Implement
   the interface your manifest `type` is actually consulted through
   (metadata providers: `MetadataSourceInterface`) — copying the demo
   `lookup()` signature gives you a plugin that enables and does
   nothing.
5. Replace the tests in **`tests/`** to match — but **keep**
   `tests/SecurityAuditCheckTest.php`, `scripts/security-audit-check.php`
   and the `composer-audit` job in `.github/workflows/test.yml`. That
   trio is the estate's whole-lock audit gate, and the guard test
   reddens your build if you neuter it.
6. Publish so the install flow can fetch you: a direct-200 archive
   URL (or a stub `plugin.json` carrying a `source` field that names
   one), plus a catalog pin for remote sources — see **Install** above
   for why a plain GitHub URL paste stops at the redirect gate today.

The plugin developer guide has the full walkthrough, including the
lifecycle diagram and the manifest event alias table:

- [`docs/plugins/developer-guide.md`](https://github.com/detain/phlix-docs/blob/master/docs/plugins/developer-guide.md)
- [`docs/plugins/manifest.md`](https://github.com/detain/phlix-docs/blob/master/docs/plugins/manifest.md)
- [`docs/plugins/install-from-url.md`](https://github.com/detain/phlix-docs/blob/master/docs/plugins/install-from-url.md)
- [`docs/plugins/trusted-plugin-list.md`](https://github.com/detain/phlix-docs/blob/master/docs/plugins/trusted-plugin-list.md)

## Running the tests locally

```bash
composer install
vendor/bin/phpunit
composer phpstan
composer phpcs
php scripts/security-audit-check.php   # same security gate CI runs
```

## License

MIT — see [`LICENSE`](LICENSE).
