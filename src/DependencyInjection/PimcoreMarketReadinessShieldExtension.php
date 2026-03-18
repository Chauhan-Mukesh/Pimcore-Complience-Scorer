<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Dependency Injection extension that loads bundle configuration and service definitions.
 */
final class PimcoreMarketReadinessShieldExtension extends Extension
{
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
