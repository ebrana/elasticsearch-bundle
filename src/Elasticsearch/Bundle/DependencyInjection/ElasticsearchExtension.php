<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\DependencyInjection;

use Elasticsearch\Mapping\Drivers\AnnotationDriver;
use Elasticsearch\Mapping\Drivers\JsonDriver;
use Override;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\DependencyInjection\Variable;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ElasticsearchExtension extends Extension
{
    /**
     * @throws \Exception
     */
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerDataCollectorConfiguration($container, $config, $loader);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder     $container
     * @param string[]                                                    $config
     * @param \Symfony\Component\DependencyInjection\Loader\XmlFileLoader $loader
     * @throws \Exception
     */
    private function registerDataCollectorConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $container->setParameter('elasticsearch.indexPrefix', $config['indexPrefix']);
        $container->setParameter('elasticsearch.hosts', $config['hosts']);
        $container->setParameter('elasticsearch.mappings', $config['mappings']);
        $container->setParameter('elasticsearch.kibana', $config['kibana']);

        $driverDefinition = match ($config['driver']['type']) {
            'attributes' => $container->register('elasticsearch.esDriver', AnnotationDriver::class),
            'json' => $container->register('elasticsearch.esDriver', JsonDriver::class),
            default => throw new RuntimeException('ES driver not found.'),
        };
        $driverDefinition->addTag('elasticsearch.esDriver');
        if ($config['driver']['keyResolver']) {
            if ($config['driver']['keyResolver'][0] === '@') {
                $reference = new Reference(ltrim($config['driver']['keyResolver'], '@'));
            } else {
                $reference = new Definition($config['driver']['keyResolver']);
            }
            $driverDefinition->addMethodCall('setKeyResolver', [$reference]);
        }
        $container->setDefinition('elasticsearch.esDriver', $driverDefinition);

        $loader->load('elasticsearch.xml');

        if (isset($config['profiling']) && $config['profiling'] && $container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $loader->load('data_collector.xml');
            $loader->load('debug.xml');
        }
    }

    /**
     * @param array<array> $config
     * @phpstan-ignore-next-line
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration((bool)$container->getParameter('kernel.debug'));
    }
}
