<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity;

/**
 * Quality dimension for a ReadinessRule — mirrors the dimensions from
 * ISO 25012 / Pimcore Data Quality Management and extends them.
 *
 * Used to group rules in the Studio UI and compute per-dimension sub-scores.
 *
 *   COMPLETENESS  — all required fields are filled in
 *   CONSISTENCY   — values are internally consistent (e.g. end_date > start_date)
 *   ACCURACY      — values are correct and plausible (min/max value rules, formats)
 *   FORMAT        — values conform to the expected format (regex, URL, e-mail)
 *   UNIQUENESS    — values are unique / not duplicated across objects (future: cross-object check)
 *   CONFORMITY    — values conform to a reference list or catalogue (in_set rules)
 *   TIMELINESS    — dates are current / not expired (date_not_past rules)
 */
enum QualityDimension: string
{
    case COMPLETENESS = 'completeness';
    case CONSISTENCY = 'consistency';
    case ACCURACY = 'accuracy';
    case FORMAT = 'format';
    case UNIQUENESS = 'uniqueness';
    case CONFORMITY = 'conformity';
    case TIMELINESS = 'timeliness';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETENESS => 'Completeness',
            self::CONSISTENCY => 'Consistency',
            self::ACCURACY => 'Accuracy',
            self::FORMAT => 'Format',
            self::UNIQUENESS => 'Uniqueness',
            self::CONFORMITY => 'Conformity',
            self::TIMELINESS => 'Timeliness',
        };
    }

    /**
     * Icon shown next to the dimension label in the Studio widget.
     */
    public function icon(): string
    {
        return match ($this) {
            self::COMPLETENESS => '📋',
            self::CONSISTENCY => '🔗',
            self::ACCURACY => '🎯',
            self::FORMAT => '🔤',
            self::UNIQUENESS => '✨',
            self::CONFORMITY => '📚',
            self::TIMELINESS => '⏱',
        };
    }
}
