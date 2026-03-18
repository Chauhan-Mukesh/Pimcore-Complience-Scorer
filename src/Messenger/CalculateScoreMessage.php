<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Messenger;

/**
 * Immutable Symfony Messenger message that triggers asynchronous score calculation
 * for all active ReadinessProfiles that target the updated object's class.
 */
final readonly class CalculateScoreMessage
{
    public function __construct(
        public readonly int $objectId,
    ) {
    }
}
