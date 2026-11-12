---
paths:
  - src/**
---

# Plugin Source Conventions

- Start every file with `<?php`, the `@copyright`/`@license` file docblock (what `scripts/add-headers.php` inserts), then `declare(strict_types=1);` and namespace `Phlix\PluginExample\`.
- Classes are `final` and implement `Phlix\Shared\Plugin\LifecycleInterface`: `onEnable(ContainerInterface)`, `onDisable()`, `subscribedEvents(): array`. A metadata provider also implements `Phlix\Shared\Metadata\MetadataSourceInterface` (the host registers the entry into its `SourceRegistry` only when it is `instanceof` that interface) and `Phlix\Shared\Plugin\ConfigurableInterface` (`configure()` is the only settings channel).
- Keep `onEnable()` cheap (store the container); do real work in the contract methods (`search()`/`getDetails()`/`getImages()`) or in listeners declared by `subscribedEvents()`.
- The host drives metadata through `search()` → `getDetails()` / `getImages()`. `lookup(string $filePath)` is a didactic fixture helper the host never calls (`getDetails()` routes through it); gate fixtures on a constant like `FIXTURE_PATH`, never an inline literal.
- Mirror new behavior in `plugin.json` (`entry`, `settings`, `events`) and add a test under `tests/`.
- `src/` is gated by static analysis in CI: `composer phpstan` (level 9 via `phpstan.neon`) and `composer phpcs` (PSR-12 via `phpcs.xml`).
