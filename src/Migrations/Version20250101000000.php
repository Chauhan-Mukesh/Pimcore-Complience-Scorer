<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema migration for the Market Readiness Shield bundle.
 *
 * Creates:
 *   - bundle_readiness_profiles
 *   - bundle_readiness_rules
 *   - bundle_readiness_scores
 */
final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Market Readiness Shield bundle tables.';
    }

    public function up(Schema $schema): void
    {
        // Profiles table
        $this->addSql(<<<'SQL'
            CREATE TABLE bundle_readiness_profiles (
                id              VARCHAR(36)     NOT NULL,
                name            VARCHAR(255)    NOT NULL,
                description     LONGTEXT        DEFAULT NULL,
                pimcore_class_name VARCHAR(255) NOT NULL,
                is_active       TINYINT(1)      NOT NULL DEFAULT 1,
                created_at      DATETIME        NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at      DATETIME        NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('CREATE INDEX idx_profiles_class_name ON bundle_readiness_profiles (pimcore_class_name)');
        $this->addSql('CREATE INDEX idx_profiles_active ON bundle_readiness_profiles (is_active)');

        // Rules table
        $this->addSql(<<<'SQL'
            CREATE TABLE bundle_readiness_rules (
                id               VARCHAR(36)     NOT NULL,
                profile_id       VARCHAR(36)     NOT NULL,
                field_path       VARCHAR(255)    NOT NULL,
                condition_type   VARCHAR(50)     NOT NULL,
                condition_value  VARCHAR(255)    DEFAULT NULL,
                weight           DOUBLE PRECISION NOT NULL,
                label            VARCHAR(255)    NOT NULL,
                tab_hint         VARCHAR(255)    DEFAULT NULL,
                sort_order       INT             NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                CONSTRAINT fk_rules_profile FOREIGN KEY (profile_id)
                    REFERENCES bundle_readiness_profiles (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('CREATE INDEX idx_rules_profile ON bundle_readiness_rules (profile_id)');

        // Scores table
        $this->addSql(<<<'SQL'
            CREATE TABLE bundle_readiness_scores (
                id                  INT             NOT NULL AUTO_INCREMENT,
                object_id           INT             NOT NULL,
                profile_id          VARCHAR(36)     NOT NULL,
                score               DOUBLE PRECISION NOT NULL,
                missing_fields_json JSON            NOT NULL,
                calculated_at       DATETIME        NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                CONSTRAINT uq_object_profile UNIQUE (object_id, profile_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('CREATE INDEX idx_scores_object_id ON bundle_readiness_scores (object_id)');
        $this->addSql('CREATE INDEX idx_scores_profile_id ON bundle_readiness_scores (profile_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS bundle_readiness_scores');
        $this->addSql('DROP TABLE IF EXISTS bundle_readiness_rules');
        $this->addSql('DROP TABLE IF EXISTS bundle_readiness_profiles');
    }
}
