# phlix-plugin-example

Reference **metadata-provider** plugin for [Phlix](https://github.com/detain/phlix) — one `final` PHP class exercising the plugin lifecycle contract. PHPUnit `^10`, depends on `detain/phlix-shared ^0.6`.

## Commands

```bash
composer install          # install deps incl. detain/phlix-shared (VCS repo)
vendor/bin/phpunit        # run the full test suite (phpunit.xml)
vendor/bin/phpunit --filter test_returns_hello_world_for_known_fixture_path
```

Regenerate the autoloader and lint after adding or renaming a class:

```bash
composer dump-autoload                       # regenerate PSR-4 map after adding a class under src/
php -l src/HelloMetadataProvider.php         # lint the entry class
composer validate                            # check composer.json is well-formed
```

## Architecture

- **Entry**: `src/HelloMetadataProvider.php` — implements `Phlix\Shared\Plugin\LifecycleInterface`; `lookup()` returns `['title' => $greeting]` for `FIXTURE_PATH`, else `[]`.
- **Manifest**: `plugin.json` — `entry` FQCN, `settings.greeting` (default `"Hello, World"`), `events: []`, `phlix_min_server_version`.
- **Package**: `composer.json` — PSR-4 `Phlix\PluginExample\` → `src/`, `Phlix\PluginExample\Tests\` → `tests/`.
- **Tests**: `tests/HelloMetadataProviderTest.php`; bootstrap `tests/bootstrap.php` autoloads then loads the stub fallback.
- **Stub**: `dev-stubs/LifecycleInterface.php` — used only when `detain/phlix-shared` is not installed.
- **Config**: `phpunit.xml` (bootstrap + `src` coverage). **CI**: `.github/workflows/test.yml` runs install + phpunit on push.

## Conventions

- `declare(strict_types=1);` at top of every `.php` file; `final` classes; constructor injection.
- Namespace `Phlix\PluginExample\`, one class per file under `src/`.
- Lifecycle: `onEnable(ContainerInterface)`, `onDisable()`, `subscribedEvents(): array` — keep `onEnable()` cheap, do work in listeners.
- Reference fixtures via `HelloMetadataProvider::FIXTURE_PATH`, never a hardcoded path.
- Plugin `name` must start with `phlix-plugin-`; bump `version` in `plugin.json`.

## Known drift

- `dev-stubs/LifecycleInterface.php` and `tests/bootstrap.php` use the legacy namespace `Phlix\Plugins\Contract\LifecycleInterface`; the live class uses `Phlix\Shared\Plugin\LifecycleInterface`. `README.md` also cites the old name. Keep the stub aligned if you edit the contract.

## Git & tooling

Branch off `main`, one concern per PR:

```bash
git checkout main && git pull && git checkout -b feature/<concern>
```

- No project-specific MCP servers needed; the GitHub MCP (if enabled) covers PRs for `git@github.com:detain/phlix-plugin-example.git`.
- Caliber skills under `.claude/skills/` (`find-skills`, `save-learning`, `setup-caliber`) keep agent configs synced.

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
  2. Run: `caliber refresh && git add CALIBER_LEARNINGS.md CLAUDE.md .claude/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

**Valid `caliber refresh` options:** `--quiet` (suppress output) and `--dry-run` (preview without writing). Do not pass any other flags — options like `--auto-approve`, `--debug`, or `--force` do not exist and will cause errors.

**`caliber config`** takes no flags — it runs an interactive provider setup. Do not pass `--provider`, `--api-key`, or `--endpoint`.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
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
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
