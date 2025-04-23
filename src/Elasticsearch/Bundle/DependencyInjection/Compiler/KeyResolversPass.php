<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class KeyResolversPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('elasticsearch.esDriver')) {
            return;
        }

        $definition = $container->findDefinition('elasticsearch.esDriver');
        $taggedServices = $container->findTaggedServiceIds('elasticsearch.key_resolver');
        $keyResolvers = [];

        foreach ($taggedServices as $id => $tags) {
            $keyResolvers[$id] = new Reference($id);
        }

        if (count($keyResolvers) > 0) {
            $definition->setArgument(0, $keyResolvers);
        }
    }
}
