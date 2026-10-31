# Changelog

All notable changes to `phlix-plugin-example` are documented here.
Historical entries are kept verbatim; corrections fold in as new
entries (estate precedent).

## Unreleased — 2026-10-30

### Fixed — W-min36 minors batch (example minors campaign)

- `src/HelloMetadataProvider.php` no longer anchors its contract story to
  `PHLIX_EXPANSION_PLAN.md` — a file that exists in no estate repo. The
  docblock now names the real provenance: `Phlix\Shared\Plugin\`
  `LifecycleInterface`, published in `detain/phlix-shared` (M-EXAMPLE-03).
- The mangled "Lifecycle notes" docblock interior (nine stray-indented
  lines in the exact sample the estate clones) is re-aligned to the
  block's ` * ` column (M-EXAMPLE-04; also the indent half of
  M-EXAMPLE-16 — the `Squiz.Commenting.DocCommentAlignment` sniff that
  keeps it fixed lands with W-min38).
- `scripts/add-headers.php` `collect()` now really prunes the excluded
  directories (`vendor/`, `.git/`, `generated/`, `node_modules/`,
  `.phpunit.cache/`) via `RecursiveCallbackFilterIterator` — returning
  false stops the descent. The previous `setFlags(CATCH_GET_CHILD)` +
  `continue` idiom pruned nothing; proven red on the old code (files
  under excluded dirs were rewritten) and green on the new
  (M-EXAMPLE-15).

Verified already cured at `99906be1` (README rewrite, ancestry-proved,
no re-fix dispatched): fork step 2 now enumerates
`phlix_min_server_version` (M-EXAMPLE-02) and states the event-alias
law — aliases are validation metadata, `subscribedEvents()` binds
(M-EXAMPLE-10).

### Fixed — W-min37 minors batch (example minors campaign)

- `composer.json`: `psr/container` narrowed `^1.1 || ^2.0` → `^2.0`.
  The `^1.1` arm was dead weight — `detain/phlix-shared` itself
  requires `^2.0`, so the lock could only ever resolve 2.x — and the
  template's job is to teach the honest constraint (M-EXAMPLE-05,
  composer half; the README file-count/event-family halves were
  already cured at `99906be1`). `composer.lock` re-hashed only
  (`composer update` segfaults in this host's venue, so the new
  content-hash was computed by `Composer\Package\Locker::`
  `getContentHash()` — the tool's own function, oracle-verified against
  the committed pair before applying; `composer validate --strict`
  passes and CI `composer install` resolves the identical tree).
- `.github/workflows/test.yml`: `actions/checkout@v6` (×2) pinned to
  commit `d23441a48e516b6c34aea4fa41551a30e30af803` — the estate
  canonical for checkout v6.1.0, the same SHA the ubuntu-26.04
  migration recorded — so the workflow teaches ONE consistent
  action-pinning posture instead of mixing SHA-pinned `setup-php`/
  `codacy` with a floating major tag (M-EXAMPLE-07).
- Tracked teaching docs (`AGENTS.md`, `CLAUDE.md`,
  `.opencode/skills/metadata-provider-class/SKILL.md`) brought
  current against the tree they describe (M-EXAMPLE-09/11): dead
  `detain/phlix` product link → `detain/phlix-server`;
  `detain/phlix-shared ^0.6` → `^0.21.0` (matching `composer.json`);
  guard suite stated at its measured 28 tests; the "Known drift"
  sections rewritten — the legacy-namespace drift they assert was
  retired at `355c445` (stub, bootstrap and README all carry the live
  `Phlix\Shared\Plugin\LifecycleInterface`), so the
  drift-documentation was itself the drift; the SKILL bootstrap claim
  inverted the same way is corrected; `Branch off main` → `master`
  (the repo has no `main` branch); CLAUDE.md's "phpcs per phpcs.xml"
  comment corrected to what the command actually runs.

### Fixed — W-min38 minors batch (example minors campaign)

- `.gitignore`: `/composer.lock` line removed (M-EXAMPLE-01/06). The
  lock is COMMITTED (the `composer-audit` CI job runs
  `composer audit --locked` and needs it in the checkout), so
  ignoring it taught forks a policy their own CI contradicts.
- `.github/workflows/test.yml` (M-EXAMPLE-08): top-level
  `permissions: contents: read` — every job in this workflow only
  reads the checkout (phpunit/phpstan/phpcs matrix + the locked
  `composer-audit` job), yet the default GITHUB_TOKEN grants write
  scope; the template should demonstrate the least-privilege posture.
- Lint configs given teeth and made authoritative (M-EXAMPLE-16/17):
  `phpstan.neon` paths now cover `scripts` as well as `src` (the audit
  gate script and the header helper were invisible to static
  analysis); `phpcs.xml` gains `<file>scripts</file>` and the
  `Squiz.Commenting.DocCommentAlignment` sniff — the class of mangled
  docblock W-min36 repaired by hand now reddens CI instead of
  recurring (named under Squiz: PHPCS 4.x ships no
  `Generic.Comment.DocCommentAlignment`; verified with `phpcs -i`),
  with `PSR1.Files.SideEffects` excluded for `scripts/*` because CLI
  entry points are side effects by design. The CI steps and composer
  scripts switched to config-driven invocations (`phpstan analyse` /
  `phpcs`, no inline paths or `--standard`) so local and CI lint agree
  — a fork editing `phpstan.neon`/`phpcs.xml` now actually changes
  what CI enforces. Teeth probed: a planted misaligned docblock under
  `scripts/` reddened `composer phpcs`; removal went clean.
- Making `scripts` PHPStan-visible surfaced 9 level-9 findings in
  `scripts/add-headers.php` (the pre-existing 13 minus the 4 the
  W-min36 rewrite dissolved) — all fixed honestly, not baselined: a
  `readFileOrFail()` helper (fail-loud `RuntimeException` instead of
  propagating `file_get_contents`' `false` through `str_contains`/
  `preg_match`/`substr`), and an `instanceof \SplFileInfo` guard
  narrowing the iterator node before `isFile()`/`getExtension()`/
  `getPathname()`. `security-audit-check.php` passed level 9 over
  `scripts` unchanged; its 121-char notice-format line was re-wrapped
  (two adjacent literals — the runtime string is byte-ident, and
  guard test `:610` pins the rendered notice). `composer.json` scripts
  section does not feed the lock `content-hash`, so no lock rotation
  was needed (verified: recomputed hash identical;
  `composer validate --strict` green).
- `AGENTS.md`/`CLAUDE.md` command-truth lines re-aimed at the
  config-driven invocations they now describe.

### Fixed — W-min39 minors batch (example minors campaign)

- `tests/SecurityAuditCheckTest.php` (M-EXAMPLE-12): the plugin-lineage
  parity trio landed — a `LANE_TOKEN` string constant
  (`S440EXAMPLEGUARDW3Q5`), `testThisIsTheS440GateSuite()`
  (self-asserting, real code so it survives tokenisation and stays
  greppable), and `testTheCommittedLockExists()` (asserts the committed
  `composer.lock`: a fork that git-ignored its lock would otherwise
  leave `composer audit --locked` measuring nothing while every other
  guard stayed green), plus the header lane-token line and lineage-port
  story. The guard suite goes 28 → 30 tests — the flagship previously
  taught the family posture it did not itself demonstrate.
- Ghost references removed (M-EXAMPLE-13): `tests/bootstrap.php` and
  `tests/HelloMetadataProviderTest.php` cited a server-side
  `SamplePluginSmokeTest` in `detain/phlix` that exists nowhere in the
  estate. Both docblocks now state the actual law: the dev-stub
  registers only when the real `Phlix\Shared\Plugin\LifecycleInterface`
  is absent, so a bare checkout self-verifies with or without the host
  application on the classpath.
- Tautological tests deleted (M-EXAMPLE-14):
  `test_on_enable_does_not_throw` / `test_on_disable_does_not_throw`
  ended in `assertTrue(true)`; the surviving void-return counterparts
  (`test_on_enable_accepts_container_and_returns_void`,
  `test_on_disable_returns_void`) strictly dominate them — any throw
  already fails those tests and they additionally assert the void
  return. Provider suite 14 → 12 tests. `AGENTS.md`/`CLAUDE.md` count
  pins re-truthed to the measured 30 guard / 12 provider in the same
  commit (suite: 42 tests, 153 assertions, previously 42/151).

### Added — M-EXAMPLE-07 (majors: API-key / outbound-call guidance on the teaching surfaces)

- The README gained **Settings & secrets** and **Outbound calls**
  sections, mirrored into the `metadata-provider-class` skill
  (Critical list + the step-7 aside) and into fork step 4. The
  template now names the blessed config seam explicitly: API keys
  are declared `"secret": true` with `"default": null` (host-masked
  via `Phlix\Plugins\SettingsMasker`, sentinel-kept on save), reach
  the instance only through `ConfigurableInterface::configure()`,
  must never surface raw in logs or returned strings, and upstream
  calls follow the `phlix-plugin-omdb` posture — https, explicit
  timeout, TLS verification on. Docs-only cure; the demo class
  still makes no network call. (The queue's `cured-by: 99906be` was
  adjacent-only: that commit rewrote the install recipe; the
  secret/outbound guidance was proven absent at the tip —
  reconciler true-miss, content-verified before this fix.)

## Unreleased — 2026-10-29

### Changed — docs(readme): make the install/usage recipe actually work

The install recipe was fiction on two independent axes, and the
usage/settings prose described channels the live host does not
consult. Every claim below was re-verified step-by-step against
`phlix-server` at current master and this repo at `5adc4f0`, using
the server's real `HttpInstaller`/`SourceUrlResolver` classes in a
scratch harness.

- **Install**: the pasted URL `raw.githubusercontent.com/.../main/plugin.json`
  404s (the branch is `master`), and even branch-corrected it fails
  the installer's stub arm because this `plugin.json` has no `source`
  field (`Stub plugin.json must contain a "source" field pointing at a
  tarball or zip.`). A GitHub repository-URL paste now stops at the
  installer's R20-02 no-redirect gate: every
  `github.com/.../archive/...` URL answers `302` and the installer
  refuses redirects before extraction. The README documents the
  procedure that verifiably completes (a `file://` tarball through the
  admin form or `php bin/phlix plugin:install`), the remote shapes
  that do answer `200`, and the SV-S2b default-deny /
  `PHLIX_PLUGINS_ALLOW_UNVERIFIED` / catalog-pin rules — including
  that the catalog click path currently hits the same redirect wall
  (server-side hop owed; reported upstream, not papered over here).
- **What it does / Use**: `lookup()` is labelled for what it is — a
  didactic method with no host call site. The contracts the host
  actually consults are named: `MetadataSourceInterface` (the
  `instanceof` gate in `PluginLoader::enable()` that registers a first
  -class metadata source) and `ConfigurableInterface::configure()`
  (the loader's "ONLY channel" for settings delivery — which is why
  the `greeting` override is stored but never reaches the instance).
  The `$container->get(...)` usage snippet, which never worked, is
  replaced by the in-process demo the test suite actually runs.
- **Coordinates**: every link pointed at `github.com/detain/phlix`,
  which 404s. Re-pointed to `detain/phlix-server`,
  `detain/phlix-docs` (`docs/plugins/{developer-guide,manifest,install-from-url,install-from-catalog,trusted-plugin-list}.md`),
  and the canonical manifest schema in `detain/phlix-shared`
  (`schemas/manifest.schema.json` — the server validates through
  `ManifestSchema` against that bundled file, not the retired
  in-tree `docs/plugins/manifest.schema.json` path the old text named).
- **Fork tour**: corrected the claims the walk exposed — event
  aliases are validation metadata (`subscribedEvents()` is what
  binds; families are `phlix.playback.*`/`phlix.library.*`/
  `phlix.user.*`, no `auth` family), the file counts, the audit-gate
  keep-it warning, and the publish step (a raw `plugin.json` paste is
  not an install path without a `source` stub).

No PHP behavior changed; the `greeting`/`lookup()` limitations are the
template's known majors (M1/M2 in the review queue), documented
truthfully here rather than papered over.
