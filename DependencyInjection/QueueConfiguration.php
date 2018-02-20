<?php

namespace SfCod\QueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sfcod_queue');

        $this->addConnections($rootNode);

        return $treeBuilder;
    }

    /**
     * Add connections config
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addConnections(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('driver')->end()
                            ->scalarNode('collection')->end()
                            ->scalarNode('connectionName')->end()
                            ->scalarNode('queue')->end()
                            ->scalarNode('expire')->end()
                            ->scalarNode('limit')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
