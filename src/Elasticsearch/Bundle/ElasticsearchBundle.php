<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle;

use Elasticsearch\Bundle\DependencyInjection\Compiler\DocumentBuilderFactorypass;
use Elasticsearch\Bundle\DependencyInjection\Compiler\KeyResolversPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ElasticsearchBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DocumentBuilderFactorypass());
        $container->addCompilerPass(new KeyResolversPass());
    }
}
