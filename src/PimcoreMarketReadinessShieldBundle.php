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
 *
 * Zero-config Studio UI: the prebuilt widget JS is served automatically
 * from the bundle's public path and loaded by Pimcore via getJsPaths().
 * No additional YAML or PHP configuration is required in the host app.
 */
final class PimcoreMarketReadinessShieldBundle extends AbstractRegisteredBundle
{
    /**
     * Public asset path for the prebuilt Studio sidebar widget.
     * Shared with PimcoreMarketReadinessShieldExtension::prepend() so both
     * registration paths always point to the same file.
     */
    public const WIDGET_JS_PATH =
        '/bundles/pimcoremarketreadinessshield/studio/dist/market-readiness-shield.iife.js';
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

    /**
     * Registers the prebuilt Studio sidebar widget JS with the Pimcore admin.
     *
     * After running `pimcore:assets:install` the file is symlinked to:
     *   public/bundles/pimcoremarketreadinessshield/studio/dist/market-readiness-shield.iife.js
     *
     * Pimcore loads this script automatically for every admin page, and the
     * script self-mounts the ReadinessPanel whenever a DataObject is active.
     *
     * @return list<string>
     */
    public function getJsPaths(): array
    {
        return [self::WIDGET_JS_PATH];
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new PimcoreMarketReadinessShieldExtension();
    }
}
