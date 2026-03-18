<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\DependencyInjection;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\PimcoreMarketReadinessShieldBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Dependency Injection extension that loads bundle configuration and service definitions.
 *
 * Also implements PrependExtensionInterface to auto-register the Studio UI widget
 * with Pimcore Studio when the studio-ui-bundle is present — no YAML config required
 * in the host application.
 */
final class PimcoreMarketReadinessShieldExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Prepend Studio UI extension config so the widget loads automatically
     * when Pimcore Studio (pimcore/studio-ui-bundle) is enabled.
     *
     * The widget JS is served from the bundle's public directory after
     * `pimcore:assets:install` has been run (or a symlink exists).
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('pimcore_studio_ui')) {
            $container->prependExtensionConfig('pimcore_studio_ui', [
                'asset_manager' => [
                    'entries' => [
                        [
                            'name'  => 'market-readiness-shield',
                            'path'  => PimcoreMarketReadinessShieldBundle::WIDGET_JS_PATH,
                        ],
                    ],
                ],
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Expose configuration values as container parameters.
        $container->setParameter('pimcore_market_readiness_shield.async_transport', $config['async_transport']);
        $container->setParameter('pimcore_market_readiness_shield.score_cache_ttl', $config['score_cache_ttl']);
        $container->setParameter('pimcore_market_readiness_shield.enable_workflow_guard', $config['enable_workflow_guard']);
        $container->setParameter('pimcore_market_readiness_shield.workflow_guards', $config['workflow_guards']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('doctrine.yaml');
    }

    public function getAlias(): string
    {
        return 'pimcore_market_readiness_shield';
    }
}
