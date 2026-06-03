# phlix-plugin-example — Agent Guide

Reference **metadata-provider** plugin for [Phlix](https://github.com/detain/phlix): one `final` PHP class exercising the plugin lifecycle contract. PHP `>=8.3`, PHPUnit `^10`, depends on `detain/phlix-shared ^0.6` (VCS repo in `composer.json`).

## Commands

```bash
composer install          # install deps incl. detain/phlix-shared
vendor/bin/phpunit        # run the suite defined by phpunit.xml
vendor/bin/phpunit --filter test_subscribed_events_is_empty
```

## Architecture

- **Entry**: `src/HelloMetadataProvider.php` — implements `Phlix\Shared\Plugin\LifecycleInterface`; `lookup()` returns `['title' => $greeting]` for `FIXTURE_PATH`, else `[]`.
- **Manifest**: `plugin.json` — `entry`, `settings.greeting` (default `"Hello, World"`), `events: []`.
- **Package**: `composer.json` — PSR-4 `Phlix\PluginExample\` → `src/`, tests → `tests/`.
- **Tests**: `tests/HelloMetadataProviderTest.php`, bootstrap `tests/bootstrap.php`; config `phpunit.xml`.
- **Stub**: `dev-stubs/LifecycleInterface.php` — fallback only when `detain/phlix-shared` is absent.
- **CI**: `.github/workflows/test.yml` runs install + phpunit on push.

## Conventions

- `declare(strict_types=1);` everywhere; `final` classes; constructor injection (`__construct(string $greeting)`).
- Namespace `Phlix\PluginExample\`; lifecycle `onEnable(ContainerInterface)` / `onDisable()` / `subscribedEvents(): array` — keep `onEnable()` cheap.
- Use `HelloMetadataProvider::FIXTURE_PATH` in tests; assert with `assertSame()`.
- Plugin `name` must start with `phlix-plugin-`.

## Known drift

- `dev-stubs/LifecycleInterface.php` + `tests/bootstrap.php` + `README.md` reference the legacy `Phlix\Plugins\Contract\LifecycleInterface`; the live class uses `Phlix\Shared\Plugin\LifecycleInterface`. Keep the stub aligned when editing the contract.

## Git

- Branch off `main`; one concern per PR. Remote: `git@github.com:detain/phlix-plugin-example.git`.

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
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

**Valid `caliber refresh` options:** `--quiet` (suppress output) and `--dry-run` (preview without writing). Do not pass any other flags — options like `--auto-approve`, `--debug`, or `--force` do not exist and will cause errors.

**`caliber config`** takes no flags — it runs an interactive provider setup. Do not pass `--provider`, `--api-key`, or `--endpoint`.

If `caliber` is not found, read `.agents/skills/setup-caliber/SKILL.md` and follow its instructions to install Caliber.
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
