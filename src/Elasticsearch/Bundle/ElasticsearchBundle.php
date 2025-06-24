<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle;

use Elasticsearch\Bundle\DependencyInjection\Compiler\DocumentBuilderFactoryPass;
use Elasticsearch\Bundle\DependencyInjection\Compiler\PostEventsPass;
use Elasticsearch\Bundle\DependencyInjection\Compiler\ResolvingKeyResolversPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ElasticsearchBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ResolvingKeyResolversPass());
        $container->addCompilerPass(new PostEventsPass());
        $container->addCompilerPass(new DocumentBuilderFactoryPass());
    }
}
