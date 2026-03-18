<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity;

/**
 * Severity level for a ReadinessRule.
 *
 * Determines how the violation is presented in the Studio UI:
 *   ERROR   — blocking issue, shown in red; counts full weight penalty
 *   WARNING — non-blocking quality concern, shown in amber
 *   INFO    — informational / best-practice suggestion, shown in blue
 */
enum SeverityLevel: string
{
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';

    public function label(): string
    {
        return match ($this) {
            self::ERROR => 'Error',
            self::WARNING => 'Warning',
            self::INFO => 'Info',
        };
    }

    /**
     * Returns the hex colour used in the Studio UI for this severity.
     */
    public function colour(): string
    {
        return match ($this) {
            self::ERROR => '#ef4444',
            self::WARNING => '#f59e0b',
            self::INFO => '#3b82f6',
        };
    }
}
