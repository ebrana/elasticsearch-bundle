<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PostEventsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('elasticsearch.esDriver')) {
            return;
        }

        $postEvents = [];
        $definition = $container->findDefinition('elasticsearch.esDriver');

        foreach (array_keys($container->findTaggedServiceIds('elasticsearch.post_event')) as $id) {
            $postEvents[$id] = new Reference($id);
        }

        if (count($postEvents) > 0) {
            $definition->setArgument(1, $postEvents);
        }
    }
}
