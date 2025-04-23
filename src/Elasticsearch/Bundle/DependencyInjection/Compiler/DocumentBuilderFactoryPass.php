<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\DependencyInjection\Compiler;

use Elasticsearch\Indexing\DocumentFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DocumentBuilderFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(DocumentFactory::class)) {
            return;
        }

        $definition = $container->findDefinition(DocumentFactory::class);
        $taggedServices = $container->findTaggedServiceIds('elasticsearch.document_builder_factory');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addBuilderFactory', [new Reference($id)]);
        }
    }
}
