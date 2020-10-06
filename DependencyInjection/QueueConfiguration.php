<?php

namespace SfCod\QueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class QueueConfiguration
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\DependencyInjection
 */
class QueueConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (Kernel::VERSION_ID >= 40300) {
            $treeBuilder = new TreeBuilder('sfcod_queue');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('sfcod_queue');
        }

        $this->addConnections($rootNode);

        return $treeBuilder;
    }

    /**
     * Add connections config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addConnections(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('namespaces')
            ->scalarPrototype()->end()
            ->end()
            ->end()
            ->children()
            ->arrayNode('connections')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('driver')->end()
            ->scalarNode('collection')->end()
            ->scalarNode('connection')->end()
            ->scalarNode('queue')->end()
            ->scalarNode('expire')->end()
            ->scalarNode('limit')->end()
            ->end()
            ->end()
            ->end()
            ->end();
    }
}
