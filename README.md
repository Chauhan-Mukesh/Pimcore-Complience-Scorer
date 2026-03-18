# Market Readiness Shield
### Compliance & Readiness Scorer for Pimcore Studio

> **Think Yoast SEO, but for enterprise data compliance.**

[![CI](https://github.com/Chauhan-Mukesh/Pimcore-Complience-Scorer/actions/workflows/ci.yml/badge.svg)](https://github.com/Chauhan-Mukesh/Pimcore-Complience-Scorer/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.2-8892BF.svg)](https://php.net)
[![Pimcore](https://img.shields.io/badge/Pimcore-%3E%3D2025.4-009688.svg)](https://pimcore.com)

---

## What is it?

**Market Readiness Shield** is a Pimcore Studio bundle that adds a permanent sidebar widget showing live *compliance readiness scores* for your DataObjects. Instead of blocking the user with a "field is mandatory" error, it shows:

> **Compliance: 85% · Missing: Active Ingredient Concentration (Jump to field), Safety Data Sheet PDF (Jump to field)**

---

## Features

| Feature | Description |
|---------|-------------|
| 🎯 **Contextual Profiles** | Define different "readiness" contexts: EU Medical Device, US Food Safety, Amazon Channel |
| 📊 **Live Score** | SVG score ring with traffic-light colour coding (red/orange/green) |
| 🔗 **Deep-link Jump** | Click "Jump ↗" to navigate directly to the missing field tab |
| ⚡ **Async Calculation** | Scores never calculated synchronously — uses Symfony Messenger |
| 🔄 **Auto-update** | Score recalculates automatically on every DataObject save |
| 🛡️ **Workflow Guard** | Block Pimcore Workflow transitions when a profile score is too low |
| 📋 **Admin Profile Manager** | Full CRUD API to manage profiles and rules without code changes |

---

## Quick Start

```bash
composer require chauhan-mukesh/pimcore-market-readiness-shield
php bin/console doctrine:migrations:migrate
php bin/console messenger:consume async
```

See [INSTALL.md](INSTALL.md) for full step-by-step instructions.

---

## Documentation

- [Installation Guide](INSTALL.md)
- [Development Plan & Checklist](PLAN.md)
- [Contributing](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)
- [Security Policy](SECURITY.md)

---

## Requirements

- PHP ≥ 8.2
- Pimcore ≥ 2025.4 (Studio UI)
- Symfony ^7.1
- MySQL 8.0+ / MariaDB 10.6+
- Symfony Messenger worker (Redis or AMQP transport recommended)

---

## License

MIT © [Mukesh Chauhan](https://github.com/Chauhan-Mukesh)
