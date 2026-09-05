# phlix-plugin-example — Agent Guide

Reference **metadata-provider** plugin for [Phlix](https://github.com/detain/phlix): one `final` PHP class exercising the plugin lifecycle contract. PHP `>=8.3`, PHPUnit `^10`, depends on `detain/phlix-shared ^0.6` (VCS repo in `composer.json`).

## Commands

```bash
composer install          # install deps incl. detain/phlix-shared
vendor/bin/phpunit        # run the suite defined by phpunit.xml
vendor/bin/phpunit --filter test_subscribed_events_is_empty
```

```bash
composer phpstan          # vendor/bin/phpstan analyse src --level=9 (phpstan.neon)
composer phpcs            # vendor/bin/phpcs src --standard=PSR12 (phpcs.xml)
php scripts/security-audit-check.php   # security gate: audits the whole lock (require + require-dev)
```

## Architecture

- **Entry**: `src/HelloMetadataProvider.php` — implements `Phlix\Shared\Plugin\LifecycleInterface`; `lookup()` returns `['title' => $greeting]` for `FIXTURE_PATH`, else `[]`.
- **Manifest**: `plugin.json` — `entry`, `settings.greeting` (default `"Hello, World"`), `events: []`, `version` `0.1.1`.
- **Package**: `composer.json` — PSR-4 `Phlix\PluginExample\` → `src/`, tests → `tests/`.
- **Tests**: `tests/HelloMetadataProviderTest.php` (14 tests: `lookup()`, `subscribedEvents()`, and the `onEnable()`/`onDisable()` no-ops), `tests/SecurityAuditCheckTest.php` (26 guard tests that execute `scripts/security-audit-check.php` and parse `.github/workflows/test.yml`), bootstrap `tests/bootstrap.php`; config `phpunit.xml`.
- **Stub**: `dev-stubs/LifecycleInterface.php` — fallback only when `detain/phlix-shared` is absent.
- **Scripts**: `scripts/security-audit-check.php` — `composer audit --locked --format=json` over the whole lock, findings labelled `[require]`/`[require-dev]`, blocking on advisories, corpus floored at `MIN_AUDITED_PACKAGES` 28 / `MIN_AUDITED_DEV_PACKAGES` 24; `scripts/add-headers.php` — idempotent `@copyright`/`@license` header insertion.
- **Lint config**: `phpstan.neon` (level 9 over `src`), `phpcs.xml` (PSR-12 declared as `<rule ref="PSR12"/>` — PHPCS 4.x uses `ref`, not `name`).
- **CI**: `.github/workflows/test.yml` runs install + phpunit + PHPStan + PHPCS on push to `master`/`main` and on PRs, and uploads the phpunit cobertura coverage report to Codacy from the `8.3` matrix leg only; a final `composer-audit` job runs `php scripts/security-audit-check.php`.

## Conventions

- `declare(strict_types=1);` everywhere, below a `@copyright`/`@license` docblock; `final` classes; constructor injection (`__construct(string $greeting)`).
- Namespace `Phlix\PluginExample\`; lifecycle `onEnable(ContainerInterface)` / `onDisable()` / `subscribedEvents(): array` — keep `onEnable()` cheap.
- Use `HelloMetadataProvider::FIXTURE_PATH` in tests; assert with `assertSame()`; mock the container with `createMock(ContainerInterface::class)` for `onEnable()`.
- Plugin `name` must start with `phlix-plugin-`.
- Never add `--no-dev`, `continue-on-error`, or an `if:` condition to the `composer-audit` job, and keep it last in `.github/workflows/test.yml` — `tests/SecurityAuditCheckTest.php` fails on each.

## Known drift

- `dev-stubs/LifecycleInterface.php` + `tests/bootstrap.php` + `README.md` reference the legacy `Phlix\Plugins\Contract\LifecycleInterface`; the live class uses `Phlix\Shared\Plugin\LifecycleInterface`. Keep the stub aligned when editing the contract.

## Git

- Branch off `main`; one concern per PR. Remote: `https://github.com/detain/phlix-plugin-example.git`.

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ AGENTS.md .agents/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

**Valid `caliber refresh` options:** `--quiet` (suppress output) and `--dry-run` (preview without writing). Do not pass any other flags — options like `--auto-approve`, `--debug`, or `--force` do not exist and will cause errors.

**`caliber config`** takes no flags — it runs an interactive provider setup. Do not pass `--provider`, `--api-key`, or `--endpoint`.

If `caliber` is not found, read `.agents/skills/setup-caliber/SKILL.md` and follow its instructions to install Caliber.
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read project learnings for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:model-config -->
## Model Configuration

Recommended default: `claude-sonnet-4-6` with high effort (stronger reasoning; higher cost and latency than smaller models).
Smaller/faster models trade quality for speed and cost — pick what fits the task.
Pin your choice (`/model` in Claude Code, or `CALIBER_MODEL` when using Caliber with an API provider) so upstream default changes do not silently change behavior.

<!-- /caliber:managed:model-config -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, read `.agents/skills/setup-caliber/SKILL.md` and follow the setup instructions.
<!-- /caliber:managed:sync -->
