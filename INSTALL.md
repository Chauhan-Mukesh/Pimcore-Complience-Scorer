# Installation Guide

## Requirements

| Dependency | Minimum Version |
|------------|-----------------|
| PHP | 8.2 |
| Pimcore | 2025.4 (Studio) |
| Symfony | 7.1 |
| Composer | 2.x |
| MySQL | 8.0 |
| Symfony Messenger transport | Redis 7+ or RabbitMQ 3.12+ |

---

## Step 1 — Install via Composer

```bash
composer require chauhan-mukesh/pimcore-market-readiness-shield
```

---

## Step 2 — Enable the Bundle

Add to `config/bundles.php`:

```php
return [
    // existing bundles ...
    CauhanMukesh\PimcoreMarketReadinessShieldBundle\PimcoreMarketReadinessShieldBundle::class => ['all' => true],
];
```

---

## Step 3 — Run Database Migrations

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

This creates three tables:

| Table | Purpose |
|-------|---------|
| `bundle_readiness_profiles` | Named profiles (e.g. "EU Medical Device") |
| `bundle_readiness_rules` | Rules within each profile |
| `bundle_readiness_scores` | Calculated scores per object+profile |

---

## Step 4 — Configure the Bundle (Optional)

Create `config/packages/pimcore_market_readiness_shield.yaml`:

```yaml
pimcore_market_readiness_shield:
    # Symfony Messenger transport name.
    # Set up the corresponding transport in config/packages/messenger.yaml.
    async_transport: async

    # Set to true to block Workflow transitions when score is below minimum.
    enable_workflow_guard: false

    # Define per-transition minimum scores (requires enable_workflow_guard: true).
    workflow_guards:
        - workflow: product_workflow
          transition: submit_for_review
          profile: logistics_profile  # Profile name (slug)
          min_score: 100
```

---

## Step 5 — Configure Messenger Transport

In `config/packages/messenger.yaml`, add your async transport:

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: "%env(MESSENGER_TRANSPORT_DSN)%"
                options:
                    queue_name: market_readiness

        routing:
            'CauhanMukesh\PimcoreMarketReadinessShieldBundle\Messenger\CalculateScoreMessage': async
```

In `.env`:

```env
# Redis
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages

# Or AMQP (RabbitMQ)
# MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

---

## Step 6 — Start the Messenger Worker

The Messenger worker processes score calculation jobs in the background.

**Development (foreground):**

```bash
php bin/console messenger:consume async --time-limit=3600 -vv
```

**Production (Supervisor recommended):**

```ini
[program:messenger_worker]
command=php /path/to/project/bin/console messenger:consume async --time-limit=3600 --memory-limit=128M
user=www-data
numprocs=2
autostart=true
autorestart=true
stderr_logfile=/var/log/messenger.err.log
stdout_logfile=/var/log/messenger.out.log
```

---

## Step 7 — Install Public Assets

```bash
php bin/console pimcore:assets:install --symlink
```

This symlinks the prebuilt Studio widget JS (included in the package) into the
Pimcore public directory. The widget loads automatically in the admin — no extra
YAML or PHP configuration is needed.

> **No rebuild required**: The compiled widget is shipped with the package and
> committed to the repository, so `pnpm` / Node.js is not required in production.

---

## Step 8 — Create Your First Readiness Profile

1. Log in to Pimcore Admin.
2. Navigate to **Market Readiness Shield → Profiles**.
3. Click **"Create Profile"**.
4. Select the Pimcore class (e.g. `Product`).
5. Add rules — for each field, set:
   - **Field Path** — dot-notation path (e.g. `sku`, `localizedfields.en.metaTitle`)
   - **Condition Type** — `not_empty`, `min_length`, `regex`, etc.
   - **Weight** — percentage this field contributes (all weights must sum to 100)
   - **Label** — shown in the widget's missing-fields list
   - **Tab Hint** — Pimcore Studio tab name for the jump link
6. Save the profile.

---

## Step 9 — Verify the Widget

1. Open any DataObject of the configured class in Pimcore Studio.
2. The **Market Readiness Shield** panel should appear in the right sidebar.
3. If no score is shown yet, save the object to trigger calculation, or click **↻**.

---

## Troubleshooting

| Symptom | Solution |
|---------|----------|
| Widget shows "Score calculation in progress…" indefinitely | Messenger worker is not running. Start with `php bin/console messenger:consume async` |
| Widget shows "Object not found" | Object ID in URL does not match a DataObject\Concrete |
| Score is stale | Click ↻ to trigger recalculation, or save the object |
| Database tables not created | Run `php bin/console doctrine:migrations:migrate` |
| `No active profiles for class…` in logs | Create a profile for the object's class name |
