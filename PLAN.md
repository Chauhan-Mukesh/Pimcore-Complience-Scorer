# Market Readiness Shield — Compliance & Readiness Scorer
## Pimcore 2025.4 → 2026.x (Pimcore Studio) — Master Development Plan

> **Status legend:** ✅ Done · 🔄 In-progress · ⬜ Pending

---

## Table of Contents
1. [Project Overview](#1-project-overview)
2. [Architecture Blueprint](#2-architecture-blueprint)
3. [Repository & Bundle Layout](#3-repository--bundle-layout)
4. [Phase 0 — Environment & Tooling Setup](#phase-0--environment--tooling-setup)
5. [Phase 1 — Bundle Skeleton](#phase-1--bundle-skeleton)
6. [Phase 2 — Domain Model & Persistence](#phase-2--domain-model--persistence)
7. [Phase 3 — Calculator Service](#phase-3--calculator-service)
8. [Phase 4 — Async Scoring Pipeline](#phase-4--async-scoring-pipeline)
9. [Phase 5 — REST API](#phase-5--rest-api)
10. [Phase 6 — Pimcore Studio Sidebar Widget](#phase-6--pimcore-studio-sidebar-widget)
11. [Phase 7 — Admin Profile Manager UI](#phase-7--admin-profile-manager-ui)
12. [Phase 8 — Workflow Integration](#phase-8--workflow-integration)
13. [Phase 9 — DataHub / API Filtering](#phase-9--datahub--api-filtering)
14. [Phase 10 — Testing](#phase-10--testing)
15. [Phase 11 — Documentation](#phase-11--documentation)
16. [Phase 12 — CI/CD & Code Quality](#phase-12--cicd--code-quality)
17. [Phase 13 — Release & Packaging](#phase-13--release--packaging)
18. [Technical Reference](#technical-reference)
19. [Risks & Mitigations](#risks--mitigations)
20. [Coding Rules & Compliance](#coding-rules--compliance)

---

## 1 · Project Overview

| Attribute | Value |
|-----------|-------|
| Bundle name | `PimcoreMarketReadinessShieldBundle` |
| Composer package | `chauhan-mukesh/pimcore-market-readiness-shield` |
| PHP | ≥ 8.2 |
| Pimcore | 2025.4 (API-version ~12) · compatible with 2026.x |
| Symfony | ^7.1 |
| Frontend | React 18 + TypeScript (Pimcore Studio UI conventions) |
| License | MIT |

### Core Philosophy
> Shift validation from **binary schema integrity** → **business-context readiness**.

Pimcore native mandatory fields block save. This bundle allows objects to save in an **incomplete/draft state** while displaying a live score, a clickable list of missing fields, and deep-link jump targets — exactly like Yoast SEO but for enterprise data compliance.

### Use-Case Matrix

| Domain | Example Profile | Key Fields Tracked |
|--------|-----------------|-------------------|
| Pharma / Medical Devices | EU Medical Device Profile | FDA NDC code, SDS PDF, active ingredient %, contraindications |
| FMCG / Food | US Food Safety Profile | Allergen tags, nutritional table, vegan/halal cert |
| Automotive | B2B Fitment Profile | Make/model/year, safety warnings, part numbers |
| Apparel | EU Localization Profile | Material composition, care instructions per locale |
| Amazon Channel | Amazon DE Readiness | Bullet points, EAN/UPC, A+ content image |

---

## 2 · Architecture Blueprint

```
┌─────────────────────────────────────────────────────────────────┐
│  Pimcore Studio UI (React / TypeScript)                         │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Sidebar Panel: "Market Readiness Shield"                │   │
│  │  ┌──────────────────────┐  ┌───────────────────────────┐ │   │
│  │  │  Score Circle (85%)  │  │  Missing Fields List      │ │   │
│  │  │  Progress Ring       │  │  [Jump to field deep-link]│ │   │
│  │  └──────────────────────┘  └───────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                 │ REST GET /api/readiness/score/{objectId}       │
└─────────────────│───────────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────────────────────────────┐
│  Symfony / Pimcore Backend                                       │
│                                                                  │
│  ScoreController ──► ObjectScoreRepository ──► DB flat table    │
│                                                                  │
│  EventSubscriber (postUpdate) ──► Messenger Bus                 │
│          │                              │                        │
│          │                    CalculateScoreMessage              │
│          │                              │                        │
│          └─────────────────► CalculateScoreHandler              │
│                                         │                        │
│                             ReadinessScoreCalculator             │
│                             ┌───────────┴───────────┐           │
│                             RuleEvaluator   ProfileRepository    │
└─────────────────────────────────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────────────┐
│  Database Tables                                                  │
│  bundle_readiness_profiles  ·  bundle_readiness_rules           │
│  bundle_readiness_scores    ·  bundle_readiness_profile_classes  │
└─────────────────────────────────────────────────────────────────┘
```

### Data Flow (Write Path)
1. Editor saves Pimcore DataObject → `pimcore.dataobject.postUpdate` event fires.
2. `ObjectUpdateSubscriber` dispatches `CalculateScoreMessage` to Symfony Messenger transport.
3. Worker process consumes the message, calls `ReadinessScoreCalculator`.
4. Calculator iterates all active profiles for the object's class, evaluates each rule.
5. Scores and missing-fields JSON are **upserted** into `bundle_readiness_scores`.

### Data Flow (Read Path)
1. Studio sidebar widget opens → calls `GET /api/readiness/score/{objectId}`.
2. `ScoreController` queries `bundle_readiness_scores` (fast flat table, indexed on `object_id`).
3. Returns JSON with scores per profile, missing fields with field paths for deep-linking.

---

## 3 · Repository & Bundle Layout

```
.
├── PLAN.md                          ← this file
├── README.md
├── INSTALL.md
├── CONTRIBUTING.md
├── CHANGELOG.md
├── SECURITY.md
├── composer.json
├── phpunit.xml.dist
├── phpstan.neon
├── .php-cs-fixer.php
├── psalm.xml
├── sonar-project.properties
├── .github/
│   └── workflows/
│       ├── ci.yml
│       └── release.yml
└── src/
    ├── PimcoreMarketReadinessShieldBundle.php
    ├── DependencyInjection/
    │   ├── PimcoreMarketReadinessShieldExtension.php
    │   └── Configuration.php
    ├── Entity/
    │   ├── ReadinessProfile.php
    │   ├── ReadinessRule.php
    │   └── ObjectScore.php
    ├── Repository/
    │   ├── ReadinessProfileRepository.php
    │   ├── ReadinessRuleRepository.php
    │   └── ObjectScoreRepository.php
    ├── Service/
    │   ├── ReadinessScoreCalculator.php
    │   ├── RuleEvaluator.php
    │   └── FieldAccessor.php
    ├── Messenger/
    │   ├── CalculateScoreMessage.php
    │   └── CalculateScoreHandler.php
    ├── EventSubscriber/
    │   └── ObjectUpdateSubscriber.php
    ├── Controller/
    │   ├── Api/
    │   │   └── ScoreController.php
    │   └── Admin/
    │       └── ProfileController.php
    ├── Migrations/
    │   └── Version20250101000000.php
    ├── Resources/
    │   ├── config/
    │   │   ├── services.yaml
    │   │   ├── routes.yaml
    │   │   └── doctrine.yaml
    │   ├── public/
    │   │   └── studio/
    │   │       ├── src/
    │   │       │   ├── ReadinessPanel.tsx
    │   │       │   ├── ScoreRing.tsx
    │   │       │   ├── MissingFieldList.tsx
    │   │       │   ├── ProfileSelector.tsx
    │   │       │   └── api.ts
    │   │       ├── package.json
    │   │       ├── tsconfig.json
    │   │       └── vite.config.ts
    │   └── views/
    │       └── admin/
    │           └── profile/
    │               └── index.html.twig
    └── Tests/
        ├── Unit/
        │   ├── Service/
        │   │   ├── ReadinessScoreCalculatorTest.php
        │   │   └── RuleEvaluatorTest.php
        │   └── Entity/
        │       └── ReadinessProfileTest.php
        └── Integration/
            ├── Controller/
            │   └── ScoreControllerTest.php
            └── Repository/
                └── ObjectScoreRepositoryTest.php
```

---

## Phase 0 — Environment & Tooling Setup

### 0.1 Prerequisites
- [ ] ⬜ PHP 8.2+ with extensions: `intl`, `pdo_mysql`, `mbstring`, `xml`, `json`
- [ ] ⬜ Composer 2.x
- [ ] ⬜ Node.js 20 LTS + pnpm 9 (for Studio UI assets)
- [ ] ⬜ MySQL 8.0+ or MariaDB 10.6+
- [ ] ⬜ Redis (Symfony Messenger transport)
- [ ] ⬜ Pimcore 2025.4 project installed (`composer create-project pimcore/skeleton`)

### 0.2 Dev Tooling
- [ ] ⬜ PHPStan level 8 (`phpstan/phpstan` + `phpstan/phpstan-symfony` + `phpstan/phpstan-doctrine`)
- [ ] ⬜ PHP-CS-Fixer 3.x (`friendsofphp/php-cs-fixer`) — PSR-12 + Symfony rules
- [ ] ⬜ Psalm 5.x (`vimeo/psalm`) — strict mode
- [ ] ⬜ PHPUnit 11.x
- [ ] ⬜ SonarQube / SonarCloud scanner (sonar-project.properties)
- [ ] ⬜ ESLint + Prettier for TypeScript

### 0.3 CI Setup
- [ ] ⬜ GitHub Actions workflow: lint → test → build assets → sonar scan
- [ ] ⬜ Branch protection: require passing CI before merge

---

## Phase 1 — Bundle Skeleton

### 1.1 composer.json
- [x] ✅ Define `name`, `description`, `type: symfony-bundle`
- [x] ✅ PHP 8.2+ constraint
- [x] ✅ Require `pimcore/pimcore: ^2025.4`
- [x] ✅ Require `symfony/messenger`, `symfony/doctrine-messenger`
- [x] ✅ PSR-4 autoload: `CauhanMukesh\PimcoreMarketReadinessShieldBundle\`
- [x] ✅ Dev-require PHPUnit, PHPStan, PHP-CS-Fixer

### 1.2 Bundle Entry Point
- [x] ✅ `PimcoreMarketReadinessShieldBundle.php` extending `AbstractRegisteredBundle`
- [x] ✅ Return correct `getNiceName()`, `getDescription()`, `getVersion()`
- [x] ✅ Register `DependencyInjection/Configuration.php`

### 1.3 DI Extension
- [x] ✅ `PimcoreMarketReadinessShieldExtension.php` loads `services.yaml`, `routes.yaml`, `doctrine.yaml`
- [x] ✅ Merges bundle config into container parameters

### 1.4 Configuration Schema
- [x] ✅ `async_transport` parameter (default: `async`)
- [x] ✅ `score_cache_ttl` parameter (default: 0 = off)
- [x] ✅ `enable_workflow_guard` boolean flag

---

## Phase 2 — Domain Model & Persistence

### 2.1 Entities

#### `ReadinessProfile`
- [x] ✅ `id` (UUID v7)
- [x] ✅ `name` (string, 255)
- [x] ✅ `description` (text, nullable)
- [x] ✅ `pimcoreClassName` (string, 255) — maps to a Pimcore DataObject class key
- [x] ✅ `isActive` (bool, default true)
- [x] ✅ `createdAt` / `updatedAt` (DateTimeImmutable)
- [x] ✅ OneToMany → `ReadinessRule`

#### `ReadinessRule`
- [x] ✅ `id` (UUID v7)
- [x] ✅ `profile` (ManyToOne → ReadinessProfile)
- [x] ✅ `fieldPath` (string, 255) — dot-notation path, e.g. `sku`, `images.0`, `bricks.NutritionBrick.calories`
- [x] ✅ `conditionType` (enum: `not_empty`, `min_length`, `max_length`, `min_value`, `max_value`, `regex`, `relation_count_min`, `file_attached`)
- [x] ✅ `conditionValue` (string, nullable) — threshold value for condition
- [x] ✅ `weight` (float, 0–100) — percentage points this rule contributes
- [x] ✅ `label` (string, 255) — human-readable label for the missing-fields list
- [x] ✅ `tabHint` (string, nullable) — Studio tab name for jump-link
- [x] ✅ `sortOrder` (int, default 0)

#### `ObjectScore`
- [x] ✅ `id` (int, auto-increment)
- [x] ✅ `objectId` (int, indexed)
- [x] ✅ `profileId` (UUID, indexed)
- [x] ✅ `score` (float, 0–100)
- [x] ✅ `missingFieldsJson` (json) — array of `{fieldPath, label, weight, tabHint}`
- [x] ✅ `calculatedAt` (DateTimeImmutable)
- [x] ✅ Unique constraint on `(objectId, profileId)`

### 2.2 Repositories
- [x] ✅ `ReadinessProfileRepository` — `findActiveByClassName(string $className): ReadinessProfile[]`
- [x] ✅ `ObjectScoreRepository` — `findByObjectId(int $objectId): ObjectScore[]`, `upsert(ObjectScore $score): void`

### 2.3 Doctrine Migration
- [x] ✅ `Version20250101000000` creates all 3 tables
- [x] ✅ Add indexes: `object_id`, `profile_id`, `(object_id, profile_id)`

---

## Phase 3 — Calculator Service

### 3.1 `FieldAccessor`
- [x] ✅ Resolves dot-notation path on a Pimcore DataObject
- [x] ✅ Handles: simple fields, localised fields, object bricks, field collections, relations, asset fields
- [x] ✅ Returns raw value or null if path not found

### 3.2 `RuleEvaluator`
- [x] ✅ Accepts a rule and a raw value, returns `bool` (passes/fails)
- [x] ✅ Condition handlers for all `conditionType` enum values
- [x] ✅ Strict type handling — no deprecated `count()` on non-countable, no `is_null` etc.

### 3.3 `ReadinessScoreCalculator`
- [x] ✅ `calculate(DataObject\Concrete $object, ReadinessProfile $profile): ObjectScore`
- [x] ✅ Iterates rules, calls `RuleEvaluator`, accumulates `totalWeight` and `achievedWeight`
- [x] ✅ Score = `achievedWeight / totalWeight * 100` (0 if totalWeight === 0.0)
- [x] ✅ Builds `missingFields` array for failed rules
- [x] ✅ Returns hydrated `ObjectScore` (not yet persisted)

---

## Phase 4 — Async Scoring Pipeline

### 4.1 Messenger Message
- [x] ✅ `CalculateScoreMessage` — immutable, holds `objectId: int`

### 4.2 Messenger Handler
- [x] ✅ `CalculateScoreHandler` implements `MessageHandlerInterface`
- [x] ✅ Loads object from Pimcore
- [x] ✅ Finds active profiles for object class
- [x] ✅ Calls `ReadinessScoreCalculator` for each profile
- [x] ✅ Persists via `ObjectScoreRepository::upsert()`
- [x] ✅ Logs success/failure via PSR-3 logger

### 4.3 Event Subscriber
- [x] ✅ `ObjectUpdateSubscriber` listens to `DataObjectEvents::POST_UPDATE`
- [x] ✅ Dispatches `CalculateScoreMessage` via `MessageBusInterface`
- [x] ✅ Uses `$event->getObject()` — no deprecated event methods

---

## Phase 5 — REST API

### 5.1 Score Endpoint
- [x] ✅ `GET /api/readiness/score/{objectId}` → `ScoreController::score()`
- [x] ✅ Returns JSON: `{ objectId, profiles: [{ profileId, profileName, score, missingFields[] }] }`
- [x] ✅ 404 if object not found
- [x] ✅ 403 if user lacks `ROLE_PIMCORE_USER`
- [x] ✅ Triggers on-demand async recalc if no score exists yet

### 5.2 Trigger Recalculation Endpoint
- [x] ✅ `POST /api/readiness/score/{objectId}/recalculate` → enqueues message, returns 202
- [x] ✅ Rate-limited (max 1 request per object per 10 seconds)

### 5.3 API Documentation
- [x] ✅ OpenAPI 3.1 annotations on controllers
- [x] ✅ Exposed via NelmioApiDoc or plain `openapi.yaml`

---

## Phase 6 — Pimcore Studio Sidebar Widget

### 6.1 Widget Architecture
- [ ] ⬜ React functional component `ReadinessPanel`
- [ ] ⬜ Registered in Studio via `PimcoreStudioUiBundle` sidebar API (plugin registration hook)
- [ ] ⬜ Fetches `GET /api/readiness/score/{objectId}` on panel mount
- [ ] ⬜ Polling: re-fetch every 5 s while score is being calculated (`calculatedAt` is stale)

### 6.2 `ScoreRing` Component
- [ ] ⬜ SVG-based circular progress ring
- [ ] ⬜ Color thresholds: red < 50, orange 50–79, green ≥ 80
- [ ] ⬜ Animated fill transition

### 6.3 `MissingFieldList` Component
- [ ] ⬜ Renders each missing field: label + weight + "Jump to field" link
- [ ] ⬜ Jump link uses `pimcore.helpers.openElement()` deep-link or Studio router push
- [ ] ⬜ Groups by `tabHint`

### 6.4 `ProfileSelector` Component
- [ ] ⬜ Dropdown to switch between profiles (if object has multiple)
- [ ] ⬜ Remembers last selected profile in `localStorage`

### 6.5 Build Pipeline
- [ ] ⬜ Vite + React + TypeScript
- [ ] ⬜ Output to `Resources/public/studio/dist/`
- [ ] ⬜ `pnpm run build` produces a single ESM entry point
- [ ] ⬜ Bundle asset registered in `PimcoreMarketReadinessShieldBundle::build()`

---

## Phase 7 — Admin Profile Manager UI

### 7.1 Profile CRUD
- [ ] ⬜ List view: table of profiles with name, class, active status, last calculated
- [ ] ⬜ Create/Edit form: name, description, pimcore class picker, rules grid
- [ ] ⬜ Delete (soft-delete via `isActive = false`)

### 7.2 Rule Editor (inline grid in profile form)
- [ ] ⬜ Add/Remove rules
- [ ] ⬜ Field path autocomplete (calls `/api/readiness/fields/{className}` to list fields)
- [ ] ⬜ Condition type dropdown, condition value input
- [ ] ⬜ Weight input with live validation (sum of weights must equal 100)
- [ ] ⬜ Label and Tab Hint inputs

### 7.3 Bulk Recalculate
- [ ] ⬜ "Recalculate All" button dispatches messages for all objects of the class
- [ ] ⬜ Progress shown via SSE or polling a progress endpoint

---

## Phase 8 — Workflow Integration

### 8.1 Workflow Guard
- [ ] ⬜ `ReadinessWorkflowGuard` implements Symfony `GuardEvent`
- [ ] ⬜ Config: `enable_workflow_guard: true`, define `min_score` per transition
- [ ] ⬜ Blocks transition if profile score < configured minimum
- [ ] ⬜ Error message: "Product cannot move to Review. Logistics Score is 72%, minimum required is 100%."

### 8.2 Configuration Example
```yaml
# config/packages/pimcore_market_readiness_shield.yaml
pimcore_market_readiness_shield:
    async_transport: async
    enable_workflow_guard: true
    workflow_guards:
        - workflow: product_workflow
          transition: submit_for_review
          profile: logistics_profile
          min_score: 100
```

---

## Phase 9 — DataHub / API Filtering

### 9.1 DataHub Query Modifier
- [ ] ⬜ `ReadinessDataHubQueryModifier` implements Pimcore DataHub `QueryModifierInterface`
- [ ] ⬜ Adds optional `readinessFilter` GraphQL argument: `{ profileId: "...", minScore: 80 }`
- [ ] ⬜ Translates to JOIN on `bundle_readiness_scores` table

### 9.2 REST Export Filter
- [ ] ⬜ Custom Pimcore REST export operator that filters by readiness score
- [ ] ⬜ Documents usage in `docs/datahub-integration.md`

---

## Phase 10 — Testing

### 10.1 Unit Tests
- [x] ✅ `RuleEvaluatorTest` — all condition types, edge cases, null values
- [x] ✅ `ReadinessScoreCalculatorTest` — score math, zero-weight guard, empty profile
- [x] ✅ `FieldAccessorTest` — dot-notation resolution, deep brick paths
- [ ] ⬜ `CalculateScoreHandlerTest` — message handler with mocked dependencies

### 10.2 Integration Tests
- [ ] ⬜ `ScoreControllerTest` — API endpoint with WebTestCase
- [ ] ⬜ `ObjectScoreRepositoryTest` — upsert idempotency, index usage
- [ ] ⬜ `ObjectUpdateSubscriberTest` — event dispatches correct message

### 10.3 Code Coverage Target
- [ ] ⬜ ≥ 80% line coverage (enforced in CI)

---

## Phase 11 — Documentation

- [x] ✅ `README.md` — overview, screenshots, quick-start
- [x] ✅ `INSTALL.md` — step-by-step installation, Messenger worker setup
- [x] ✅ `CONTRIBUTING.md` — coding standards, PR guidelines
- [x] ✅ `CHANGELOG.md` — KEEP-A-CHANGELOG format
- [x] ✅ `SECURITY.md` — vulnerability reporting policy
- [ ] ⬜ `docs/architecture.md` — sequence diagrams
- [ ] ⬜ `docs/rule-conditions.md` — all condition types with examples
- [ ] ⬜ `docs/workflow-integration.md`
- [ ] ⬜ `docs/datahub-integration.md`
- [ ] ⬜ `docs/studio-widget.md` — how to register & customize the widget
- [ ] ⬜ OpenAPI spec `docs/openapi.yaml`

---

## Phase 12 — CI/CD & Code Quality

### 12.1 GitHub Actions Workflow (`ci.yml`)
- [x] ✅ `composer validate --strict`
- [x] ✅ PHP-CS-Fixer dry-run
- [x] ✅ PHPStan level 8
- [x] ✅ PHPUnit with coverage
- [ ] ⬜ pnpm install + build Studio assets
- [ ] ⬜ SonarCloud scan

### 12.2 SonarQube Configuration
- [x] ✅ `sonar-project.properties`
- [x] ✅ Sources: `src/`
- [x] ✅ Tests: `src/Tests/`
- [x] ✅ PHP coverage report path
- [ ] ⬜ JS/TS coverage from Vitest

### 12.3 Code Quality Gates
- [ ] ⬜ No blocker/critical SonarQube issues
- [ ] ⬜ Duplication < 3%
- [ ] ⬜ PHPStan level 8 zero errors
- [ ] ⬜ PHP-CS-Fixer zero violations
- [ ] ⬜ ESLint zero errors

---

## Phase 13 — Release & Packaging

### 13.1 Versioning
- [ ] ⬜ Semantic versioning: `1.0.0-beta.1`
- [ ] ⬜ Tag on GitHub, trigger release workflow
- [ ] ⬜ Publish to Packagist

### 13.2 Release Checklist
- [ ] ⬜ All Phase 0–10 items complete
- [ ] ⬜ CHANGELOG updated
- [ ] ⬜ Docs complete
- [ ] ⬜ CI green
- [ ] ⬜ SonarCloud quality gate passed

---

## Technical Reference

### Pimcore 2025.4 / 2026.x Key APIs (No Deprecated Usage)

| Purpose | API / Class |
|---------|-------------|
| DataObject load | `DataObject\Concrete::getById(int $id)` |
| DataObject events | `Pimcore\Event\DataObjectEvents::POST_UPDATE` |
| Admin auth check | `$this->denyAccessUnlessGranted('ROLE_PIMCORE_USER')` |
| Bundle registration | Extend `Pimcore\Extension\Bundle\AbstractRegisteredBundle` |
| Studio UI registration | `PimcoreStudioUiBundle` plugin API (sidebar panel hook) |
| Doctrine migrations | `DoctrineMigrationsBundle` |
| Messenger transport | Symfony Messenger `async` transport (AMQP or Redis) |
| UUID generation | `symfony/uid` `Uuid::v7()` |
| Logging | PSR-3 `LoggerInterface` injection |

### Condition Type Reference

| Condition | Description | `conditionValue` |
|-----------|-------------|-----------------|
| `not_empty` | Value is not null, "", [], or 0 | — |
| `min_length` | String length ≥ N | `"50"` |
| `max_length` | String length ≤ N | `"255"` |
| `min_value` | Numeric value ≥ N | `"0.1"` |
| `max_value` | Numeric value ≤ N | `"9999"` |
| `regex` | Value matches regex pattern | `"/^[A-Z]{2}/"` |
| `relation_count_min` | Relation array count ≥ N | `"1"` |
| `file_attached` | Asset / document relation is not empty | — |

### Score Math
```
totalWeight  = Σ rule.weight  (all rules in profile)
passedWeight = Σ rule.weight  (rules that PASS)
score        = (passedWeight / totalWeight) * 100
```

> If `totalWeight === 0.0` → score = 0.  
> Score is rounded to 2 decimal places.

---

## Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Synchronous score calculation on every save | High latency, timeouts | Async Messenger pipeline — never calculate on the request thread |
| Deep field paths on complex objects (bricks, collections) | Calculator crashes or returns wrong value | `FieldAccessor` with thorough null-safety + unit tests for every field type |
| Stale scores in flat table | UI shows outdated data | `calculatedAt` timestamp shown in widget; polling until fresh |
| Total weight ≠ 100% in profile | Misleading percentages | Admin UI enforces sum = 100 with live validation |
| Studio UI plugin API changes between 2025.x and 2026.x | Widget breaks on upgrade | Abstract UI integration behind a thin adapter layer; pin Studio peer dependency |
| Missing Messenger worker | Scores never updated | INSTALL.md documents worker setup; bundle logs a warning if queue grows stale |

---

## Coding Rules & Compliance

### PHP
- PHP 8.2+ typed properties (no `mixed` without justification), constructor promotion, readonly where possible
- `final` classes by default; only open when extension is needed
- `enum` for `conditionType` — no string magic constants
- PHPDoc only where types cannot be expressed natively
- PSR-12 code style, enforced by PHP-CS-Fixer
- PHPStan level 8 — no `@phpstan-ignore`
- No `count()` on non-countable (use `is_array()` guard)
- No `date()` / `time()` — use `DateTimeImmutable` / `Clock`
- No deprecated Pimcore APIs (check against Pimcore 2025.4 migration guide)
- All DB queries via Doctrine ORM or DBAL — no raw SQL strings
- No `eval()`, no `exec()`, no dynamic class instantiation without type guard

### TypeScript / React
- Strict TypeScript (`"strict": true`)
- React 18 hooks only — no class components
- No `any` type
- ESLint + Prettier enforced
- Dependency array completeness enforced by `eslint-plugin-react-hooks`

### Security
- CSRF protection on all state-changing endpoints (Symfony CSRF token)
- Input validation on all API endpoints (Symfony Validator constraints)
- No secrets in source code
- Rate limiting on recalculate endpoint
- Access control: `ROLE_PIMCORE_USER` for read, `ROLE_PIMCORE_ADMIN` for write

### SonarQube / Sonar Compliance
- No code smells rated BLOCKER or CRITICAL
- Cognitive complexity < 15 per function
- No duplicated blocks > 10 lines
- Test coverage ≥ 80%

---

*Last updated: 2025-01-01 · Version: 0.1.0-plan*
