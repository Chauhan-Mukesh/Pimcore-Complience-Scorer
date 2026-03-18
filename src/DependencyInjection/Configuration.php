<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the bundle configuration schema.
 *
 * Example YAML:
 *   pimcore_market_readiness_shield:
 *     async_transport: async
 *     score_cache_ttl: 0
 *     enable_workflow_guard: true
 *     workflow_guards:
 *       - workflow: product_workflow
 *         transition: submit_for_review
 *         profile: logistics_profile
 *         min_score: 100
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_market_readiness_shield');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('async_transport')
                    ->defaultValue('async')
                    ->info('Symfony Messenger transport name to use for async score calculation.')
                ->end()
                ->integerNode('score_cache_ttl')
                    ->defaultValue(0)
                    ->min(0)
                    ->info('Cache TTL in seconds for score API responses. 0 = disabled.')
                ->end()
                ->booleanNode('enable_workflow_guard')
                    ->defaultFalse()
                    ->info('Enable Symfony Workflow guards that block transitions when score is too low.')
                ->end()
                ->arrayNode('workflow_guards')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('workflow')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('transition')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('profile')->isRequired()->cannotBeEmpty()->end()
                            ->floatNode('min_score')->defaultValue(100.0)->min(0.0)->max(100.0)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
