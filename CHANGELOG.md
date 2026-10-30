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
