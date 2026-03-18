<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\DependencyInjection\PimcoreMarketReadinessShieldExtension;
use Pimcore\Extension\Bundle\AbstractRegisteredBundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * Market Readiness Shield Bundle entry-point.
 *
 * Registers the bundle with Pimcore 2025.4+ / 2026.x and Symfony 7.
 * No deprecated Pimcore or Symfony APIs are used.
 */
final class PimcoreMarketReadinessShieldBundle extends AbstractRegisteredBundle
{
    public function getNiceName(): string
    {
        return 'Market Readiness Shield';
    }

    public function getDescription(): string
    {
        return 'Live compliance & readiness scoring for Pimcore DataObjects with a Studio sidebar widget.';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new PimcoreMarketReadinessShieldExtension();
    }
}
