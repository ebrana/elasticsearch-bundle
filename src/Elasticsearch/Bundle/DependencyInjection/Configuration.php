<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\DependencyInjection;

use Override;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

readonly class Configuration implements ConfigurationInterface
{
    public function __construct(private bool $debug)
    {
    }

    #[Override]
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
                ->enumNode("type")->values(['attributes'])->defaultValue('attributes')->end()
                ->scalarNode('keyResolver')->defaultValue(null)->end()
            ->end()
        ->end();
        $children->scalarNode('kibana')->defaultValue('http://localhost:5601')->end();
        $children->arrayNode('hosts')->scalarPrototype()->defaultValue(['localhost:9200'])->end();
        $children->arrayNode('mappings')->scalarPrototype()->end();
        $children->end();
    }
}
