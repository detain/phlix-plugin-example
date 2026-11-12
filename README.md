# phlix-plugin-example

[![tests](https://github.com/detain/phlix-plugin-example/actions/workflows/test.yml/badge.svg)](https://github.com/detain/phlix-plugin-example/actions/workflows/test.yml)

> Reference **metadata-provider** plugin for [Phlix](https://github.com/detain/phlix-server)
> — the smallest plugin that demonstrates the wiring a real host consults.

This repo is the canonical hello-world template for Phlix plugin
authors. It does almost nothing on purpose — but it is armed exactly
like a real plugin: it implements the three shared contracts a
metadata provider is consulted through
(`LifecycleInterface` + `ConfigurableInterface` +
`MetadataSourceInterface`), answers the metadata triad with a fixed
greeting for one well-known fixture, delivers the manifest `greeting`
setting through `configure()`, and ships with a CI workflow plus
PHPUnit tests so you can fork it as a starter. Read **"What it does"**
below for the call-path tour — `lookup()` is a didactic helper the
host never calls, and the live call paths are named there.

## What it does

The host drives this plugin through three contracts, all implemented:

- **Lifecycle** — `Phlix\Shared\Plugin\LifecycleInterface`
  (`onEnable()`, `onDisable()`, `subscribedEvents()`): the
  instantiate/enable/disable contract the loader runs every plugin
  through.
- **Settings delivery** — `Phlix\Shared\Plugin\ConfigurableInterface`
  (`configure(array $settings)`). Per the loader's own words this is
  "the ONLY channel by which a plugin receives its configured API
  keys/options" (`Phlix\Plugins\PluginLoader` in `phlix-server`,
  applied once between construction and `onEnable()`). That is how an
  operator's `greeting` override from `plugin.json` reaches the
  instance — and it is the seam this template exists to demonstrate.
- **Metadata pipeline** —
  `Phlix\Shared\Metadata\MetadataSourceInterface`
  (`sourceName()`, `supportedMediaTypes()`, `search()`,
  `getDetails()`, `getImages()`). `PluginLoader::enable()` registers
  an entry instance into the metadata `SourceRegistry` **only** when
  it is `instanceof` that interface — a clone that drops it enables
  cleanly and does nothing forever.

Through the triad, the reference source is `example`
(`sourceName()`, the slug an admin adds to a
`metadata.provider_priority` list), registered for `movie` items
(`supportedMediaTypes()`), and:

- `search('hello')` returns `[['id' => 'hello', 'title' => 'Hello, World']]`
  — the fixture, best-match first; any other query returns `[]`.
- `getDetails('hello')` returns `['title' => 'Hello, World']`; any
  other id returns `[]`. It routes through `lookup()`, below, so the
  fixture shape lives in one place.
- `getImages()` always returns `[]` — the reference ships no artwork,
  and an empty map is the contract-legal way to say so.

> **`lookup()` is a didactic method: the host never calls it.** There
> is no plugin-entry `lookup()` call site anywhere in
> `phlix-server/src`. It is the shape this template predates, kept as
> the fixture helper `getDetails()` delegates to — calling
> `lookup('/test/hello.mkv')` returns `['title' => 'Hello, World']`
> (any other path `[]`), but the live path into that code is the triad
> above. Forkers replace the triad bodies, not `lookup()` alone.

The live metadata plugins (e.g.
`phlix-plugin-omdb`) show the production arms of the same trio; the
[developer guide](https://github.com/detain/phlix-docs/blob/master/docs/plugins/developer-guide.md)
tours the contracts.

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

On a live host, install and enable the plugin (see **Install**); the
loader configures it from the persisted settings and registers it as
the `example` metadata source, so `search('hello')` /
`getDetails('hello')` answer for movie items — with the operator's
`greeting` override applied.

The in-process demo below mirrors that host path, and
`tests/HelloMetadataProviderTest.php` pins it line for line
(`test_readme_in_process_demo_holds`) alongside behavioral tests for
the `configure()` absorb rule (missing, empty-string and non-string
`greeting` values keep the last good greeting) and the `search()` →
`getDetails()` pair:

```php
$provider = new \Phlix\PluginExample\HelloMetadataProvider();
$provider->configure(['greeting' => 'Bonjour, Monde']); // the host's settings channel
$provider->getDetails('hello');   // ['title' => 'Bonjour, Monde']
$provider->lookup('/test/hello.mkv'); // ['title' => 'Bonjour, Monde'] (didactic helper)
$provider->lookup('/anything/else');  // []
```

The fixture path lives at
`Phlix\PluginExample\HelloMetadataProvider::FIXTURE_PATH` if you want
to reference it from tests.

## Settings & secrets

A real fork outgrows the demo quickly — usually by adding an upstream
API key (TMDB, TVDB, Fanart.tv, the classic metadata sources). The
`configure()` seam itself is demonstrated above (it is how the
`greeting` arrives); the network rules this template cannot
demonstrate are stated here instead of being left to each cloner's
improvisation:

- **A secret has no place in the manifest.** `plugin.json` ships in
  your public archive and is the bytes the installer fetches; a
  `default` carrying a real key leaks it to every clone. Declare the
  key with `"secret": true` and `"default": null` — `required: true`
  is still fine, the host null-fills (see the Note in **Fork as a
  starter** step 2) — exactly the shape
  [`phlix-plugin-omdb`](https://github.com/detain/phlix-plugin-omdb)
  uses for its `api_key`.
- **`"secret": true` is the blessed config seam.** Only flagged keys
  are host-managed secrets: the admin API
  (`GET /api/v1/admin/plugins/{name}`) renders their stored values
  masked (`***` — `Phlix\Plugins\SettingsMasker` server-side), and
  the configure-form save keeps the stored secret when the UI
  round-trips the mask sentinel unchanged. A key declared *without*
  the flag round-trips visibly through admin surfaces — the flag,
  not the key's name, is what makes a setting a secret.
- **Read keys only through `configure()`.**
  `Phlix\Shared\Plugin\ConfigurableInterface::configure(array $settings)`
  is, per the loader's own words, "the ONLY channel by which a plugin
  receives its configured API keys/options"
  (`phlix-server/src/Plugins/PluginLoader.php`). Stash it in a private
  property; never write the whole settings array — or any one secret
  value — into a log line.
- **Never let a raw secret reach an output surface**, including
  strings you return as `title` and URLs you print on failure. When
  the upstream demands the key in a query string, redact it on every
  log/exception surface — `OmdbApi::MASKED_APIKEY` +
  `redactSecrets()` in `phlix-plugin-omdb` are the reference.

## Outbound calls

`HelloMetadataProvider` never touches the network; your replacement
probably will. The estate posture to copy — demonstrated by the live
metadata plugins, not by this demo:

- **https, with no runtime exception.** The installer is HTTPS-only
  (SV-S2b — see **Install** above); mirroring that gate in your own
  calls costs nothing: pin an `https://` base URL as a constant
  (`OmdbApi::API_BASE`).
- **Explicit timeout, TLS verification on.** Use the HTTP client the
  host runtime already carries — `Workerman\Http\Client`,
  constructed with `'timeout' => …` — and leave `verify_ssl` enabled.
  `phlix-plugin-omdb` defaults verification on and exposes the
  opt-out as a deliberate `use_ssl_verification` setting rather than
  silently disabling it. Never reach for `file_get_contents()` or a
  raw socket without a timeout: a blocking call with no bound can
  stall the worker process your plugin shares with the host.
- **Bound your own retries.** `OmdbApi::enforceRateLimit()` shows a
  plugin staying inside its upstream's contract; retry storms are
  the plugin's problem, not the provider's fault budget.

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
   behind the contract methods. The reference class already implements
   the interfaces your manifest `type` is consulted through (metadata
   providers: `MetadataSourceInterface`, settings via
   `ConfigurableInterface`) — keep the interfaces and replace their
   bodies with real upstream calls. The didactic `lookup()` helper is
   the one piece you may drop: the host never calls it, so a fork
   that keeps only that shape enables and does nothing. If the new
   class needs an API key or calls an upstream service, follow
   **Settings & secrets** and **Outbound calls**
   above — do not improvise key handling.
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
