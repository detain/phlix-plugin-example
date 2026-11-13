---
paths:
  - tests/**
---

# Test Conventions

- PHPUnit `^12.5` via `vendor/bin/phpunit`; config in `phpunit.xml`, bootstrap `tests/bootstrap.php`.
- Test classes are `final`, namespace `Phlix\PluginExample\Tests\`, extend `PHPUnit\Framework\TestCase`.
- Name methods `test_snake_case_description()`; assert exact results with `assertSame()`.
- Reference the fixture via `HelloMetadataProvider::FIXTURE_PATH`, not a literal string.
- Cover the void lifecycle hooks too: pass `$this->createMock(ContainerInterface::class)` to `onEnable()` and `assertNull()` the return of `onEnable()` / `onDisable()`.
- `tests/bootstrap.php` only requires `vendor/autoload.php`: `detain/phlix-shared` is a hard Composer dependency, so the real `Phlix\Shared\*` contracts always resolve. There is no `dev-stubs/` fallback or `interface_exists` guard — don't reintroduce one.
- `tests/SecurityAuditCheckTest.php` is the exception to the naming rule: CI guard tests use camelCase `testDescription()`, execute `scripts/security-audit-check.php`, and parse `.github/workflows/test.yml`. Keep plugin tests in the `test_snake_case` style.
