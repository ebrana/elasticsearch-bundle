<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

readonly class Configuration implements ConfigurationInterface
{
    public function __construct(private bool $debug)
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('elasticsearch');
        $rootNode = $treeBuilder->getRootNode();

        if ($rootNode instanceof ArrayNodeDefinition) {
            $this->addElasticsearchSection($rootNode);
        }

        return $treeBuilder;
    }

    private function addElasticsearchSection(ArrayNodeDefinition $node): void
    {
        $children = $node->children();
        $children->booleanNode('profiling')->defaultValue($this->debug)->end();
        $children->scalarNode('indexPrefix')->end();
        $children->arrayNode('driver')
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode("type")->values(['attributes', 'json'])->cannotBeEmpty()->defaultValue('attributes')->end()
                ->scalarNode('keyResolver')->defaultValue(null)->end()
            ->end()
        ->end();
        $children->scalarNode('kibana')->defaultValue('http://localhost:5601')->end();
        $children->scalarNode('cache')->defaultNull()->end();
        $children->arrayNode('connection')
            ->children()
                ->arrayNode('hosts')->scalarPrototype()->defaultValue(['localhost:9200'])->end()->end()
                ->scalarNode('username')->cannotBeEmpty()->end()
                ->scalarNode('password')->cannotBeEmpty()->end()
                ->scalarNode('cloudId')->cannotBeEmpty()->end()
                ->integerNode('retries')->end()
                ->booleanNode('elasticMetaHeader')->defaultTrue()->end()
                ->scalarNode('logger')->cannotBeEmpty()->end()
                ->scalarNode('httpClient')->cannotBeEmpty()->end()
                ->scalarNode('asyncHttpClient')->cannotBeEmpty()->end()
                ->scalarNode('nodePool')->cannotBeEmpty()->end()
                ->arrayNode('httpClientOptions')->scalarPrototype()->end()->end()
                ->arrayNode('api')
                    ->children()
                        ->scalarNode('apiKey')->cannotBeEmpty()->end()
                        ->scalarNode('id')->end()
                    ->end()
                ->end()
                ->arrayNode('ssl')
                    ->children()
                        ->scalarNode('sslCA')->cannotBeEmpty()->end()
                        ->arrayNode('sslCert')
                            ->children()
                                ->scalarNode('cert')->cannotBeEmpty()->end()
                                ->scalarNode('password')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->arrayNode('sslKey')
                            ->children()
                                ->scalarNode('key')->cannotBeEmpty()->end()
                                ->scalarNode('password')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->booleanNode('sslVerification')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end()
        ->end();
        $children->arrayNode('mappings')->scalarPrototype()->end();
        $children->end();
    }
}
