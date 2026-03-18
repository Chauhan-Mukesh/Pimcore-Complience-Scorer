# Changelog

All notable changes to **Market Readiness Shield** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- Initial bundle scaffold for Pimcore 2025.4+ / Symfony 7.1
- `ReadinessProfile` entity with UUID v7 primary key
- `ReadinessRule` entity with `ConditionType` backed enum
- `ObjectScore` entity with flat indexed table for fast UI reads
- `FieldAccessor` service — dot-notation path resolution for DataObjects
- `RuleEvaluator` service — all 8 condition types with strict PHP 8.2 type handling
- `ReadinessScoreCalculator` service — async score computation
- `CalculateScoreMessage` / `CalculateScoreHandler` — Symfony Messenger async pipeline
- `ObjectUpdateSubscriber` — triggers score recalculation on `DataObjectEvents::POST_UPDATE`
- REST API: `GET /api/readiness/score/{objectId}`, `POST /api/readiness/score/{objectId}/recalculate`
- Admin API: full CRUD for Readiness Profiles (`/api/readiness/admin/profiles`)
- Pimcore Studio sidebar widget (React 18 + TypeScript)
  - `ReadinessPanel` — main widget with polling
  - `ScoreRing` — SVG circular progress with colour thresholds
  - `MissingFieldList` — grouped missing fields with jump links
  - `ProfileSelector` — multi-profile dropdown with localStorage persistence
- Doctrine migration creating all bundle tables with proper indexes
- PHPUnit test suite (unit: RuleEvaluator, Calculator, Profile entity)
- GitHub Actions CI workflow (PHP 8.2/8.3 matrix, Studio UI build, SonarCloud)
- Comprehensive documentation: README, INSTALL, CONTRIBUTING, SECURITY, PLAN

---

## [1.0.0] — TBD

*First stable release — pending completion of Phase 6–9 features.*

[Unreleased]: https://github.com/Chauhan-Mukesh/Pimcore-Complience-Scorer/compare/HEAD
