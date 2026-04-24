# Contributing

Thanks for your interest in contributing to `crumbls/subscriptions`. Bug reports, feature proposals, and pull requests are all welcome.

## Local setup

```bash
git clone https://github.com/Crumbls/subscriptions.git
cd subscriptions
composer install
```

## Running the quality checks

`composer test` runs the full pipeline used by CI:

```bash
composer test               # lint + type coverage + typos + unit + types + refactor
```

Individual pieces:

```bash
composer test:lint          # Pint --test (code style, no fixes)
composer test:unit          # Pest with 100% coverage enforced
composer test:types         # PHPStan
composer test:refactor      # Rector --dry-run
composer test:type-coverage # pest --type-coverage --exactly=100
composer test:typos         # peck
composer lint               # Pint in fix mode
composer refactor           # Rector in fix mode
```

## Pull requests

- Open the PR against `main`.
- Run `composer test` locally before pushing; CI runs the same commands across the full PHP/Laravel matrix.
- Include tests for behavior changes. Feature tests live in `tests/Feature/`, unit tests in `tests/Unit/`.
- Breaking changes belong behind a version bump — note them in `CHANGELOG.md` and add upgrade steps to `UPGRADING.md`.

## Reporting issues

Open a GitHub issue with:

- Affected version (`composer show crumbls/subscriptions`).
- PHP and Laravel versions.
- A minimal reproduction — ideally a failing test.
- Expected vs. actual behavior.

## Security issues

Please do not open public issues for security vulnerabilities. See `SECURITY.md` for reporting channels.
