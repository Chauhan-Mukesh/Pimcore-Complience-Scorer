# Contributing to Market Readiness Shield

Thank you for considering contributing! This document outlines the process and standards.

---

## Development Setup

```bash
# Clone the repository
git clone https://github.com/Chauhan-Mukesh/Pimcore-Complience-Scorer.git
cd Pimcore-Complience-Scorer

# Install PHP dependencies
composer install

# Install Studio UI dependencies
cd src/Resources/public/studio
pnpm install
```

---

## Coding Standards

### PHP

- **PHP 8.2+** — use typed properties, constructor promotion, readonly, enums
- **PSR-12** code style, enforced by **PHP-CS-Fixer** (`vendor/bin/php-cs-fixer fix`)
- **PHPStan level 8** — zero errors required (`vendor/bin/phpstan analyse`)
- `declare(strict_types=1)` in every file
- `final` classes by default — only open when extension is explicitly designed
- No deprecated Pimcore or Symfony APIs
- No raw SQL — use Doctrine ORM/DBAL
- All event listeners use the current Pimcore event API (`DataObjectEvents::POST_UPDATE`, etc.)

### TypeScript / React

- **Strict TypeScript** (`"strict": true` in tsconfig.json)
- React 18 functional components with hooks — no class components
- No `any` type
- `eslint` + `prettier` enforced (`pnpm run lint`)

### Commit Messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add relation_count_min condition type
fix: handle null object ID in event subscriber
docs: add workflow integration guide
chore: update phpunit to 11.1
```

---

## Running Tests

```bash
# PHP unit tests
vendor/bin/phpunit --testsuite unit

# PHP-CS-Fixer dry-run
vendor/bin/php-cs-fixer fix --dry-run --diff

# TypeScript type-check
cd src/Resources/public/studio && pnpm run type-check
```

---

## Pull Request Process

1. Fork the repository and create a feature branch: `git checkout -b feat/my-feature`
2. Ensure all tests pass and code quality checks are green
3. Update `CHANGELOG.md` under `[Unreleased]`
4. Open a PR against `main` with a clear description

---

## Reporting Issues

Please use the [GitHub issue tracker](https://github.com/Chauhan-Mukesh/Pimcore-Complience-Scorer/issues).
Include: Pimcore version, PHP version, bundle version, steps to reproduce, expected vs actual behaviour.
