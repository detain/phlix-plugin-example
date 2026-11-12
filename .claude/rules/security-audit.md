---
paths:
  - scripts/**
  - .github/workflows/**
---

# Security Audit Gate

- `scripts/security-audit-check.php` is the CI security gate: `composer audit --locked --format=json --no-interaction` over the WHOLE lock, each finding labelled `[require]` or `[require-dev]`.
- Never pass `--no-dev`: a development dependency still runs on CI and on workstations (S246 — CVE-2026-67434 in `squizlabs/php_codesniffer` was invisible to `--no-dev` gates).
- Verdicts: any advisory blocks (exit 1); abandoned packages and `config.audit.ignore` entries are loud but non-blocking; a missing/unparseable lock or payload, an unreachable advisory repository, and a corpus below its floor all block.
- Floors are declared in the script (`MIN_AUDITED_PACKAGES` 28, `MIN_AUDITED_DEV_PACKAGES` 24) and asserted by `tests/SecurityAuditCheckTest.php` — change script and test together.
- The only escape hatch is `config.audit.ignore` in `composer.json` with a written reason; there is no baseline file and no ignore list in the script.
- Keep the `composer-audit` job LAST in `.github/workflows/test.yml`, with no `continue-on-error` and no `if:` condition — the guard test slices the file from `composer-audit:` to the end.
- `scripts/add-headers.php` is idempotent: a second run must produce no diff.
