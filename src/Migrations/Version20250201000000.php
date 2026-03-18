<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Advanced quality-checker migration.
 *
 * Adds to bundle_readiness_rules:
 *   - severity          VARCHAR(20)  — 'error' | 'warning' | 'info'
 *   - dimension         VARCHAR(30)  — quality dimension enum value
 *   - error_message     VARCHAR(512) — custom message shown on failure
 *   - condition_value extended to 512 chars for longer regex / set lists
 *
 * Adds to bundle_readiness_scores:
 *   - dimension_scores  JSON         — per-dimension sub-scores map
 *   - severity_counts   JSON         — violation counts per severity level
 */
final class Version20250201000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add severity, dimension, and advanced scoring columns to rules and scores tables.';
    }

    public function up(Schema $schema): void
    {
        // Extend condition_value column length.
        $this->addSql(
            'ALTER TABLE bundle_readiness_rules MODIFY condition_value VARCHAR(512) DEFAULT NULL',
        );

        // Add severity column.
        $this->addSql(
            "ALTER TABLE bundle_readiness_rules ADD COLUMN severity VARCHAR(20) NOT NULL DEFAULT 'error' AFTER tab_hint",
        );

        // Add dimension column.
        $this->addSql(
            "ALTER TABLE bundle_readiness_rules ADD COLUMN dimension VARCHAR(30) NOT NULL DEFAULT 'completeness' AFTER severity",
        );

        // Add optional custom error message.
        $this->addSql(
            'ALTER TABLE bundle_readiness_rules ADD COLUMN error_message VARCHAR(512) DEFAULT NULL AFTER label',
        );

        // Add per-dimension sub-scores JSON column to scores table.
        $this->addSql(
            "ALTER TABLE bundle_readiness_scores ADD COLUMN dimension_scores JSON NOT NULL DEFAULT (JSON_OBJECT()) AFTER missing_fields_json",
        );

        // Add severity counts JSON column to scores table.
        $this->addSql(
            "ALTER TABLE bundle_readiness_scores ADD COLUMN severity_counts JSON NOT NULL DEFAULT (JSON_OBJECT('error', 0, 'warning', 0, 'info', 0)) AFTER dimension_scores",
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bundle_readiness_rules DROP COLUMN severity');
        $this->addSql('ALTER TABLE bundle_readiness_rules DROP COLUMN dimension');
        $this->addSql('ALTER TABLE bundle_readiness_rules DROP COLUMN error_message');
        $this->addSql('ALTER TABLE bundle_readiness_rules MODIFY condition_value VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE bundle_readiness_scores DROP COLUMN dimension_scores');
        $this->addSql('ALTER TABLE bundle_readiness_scores DROP COLUMN severity_counts');
    }
}
