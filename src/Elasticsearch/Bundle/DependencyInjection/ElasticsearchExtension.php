<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\DependencyInjection;

use Elasticsearch\Indexing\Builders\DefaultDocumentBuilderFactory;
use Elasticsearch\Indexing\Interfaces\DocumentBuilderFactoryInterface;
use Elasticsearch\Mapping\Drivers\AnnotationDriver;
use Elasticsearch\Mapping\Drivers\Events\PostEventInterface;
use Elasticsearch\Mapping\Drivers\JsonDriver;
use Elasticsearch\Mapping\Drivers\Resolvers\KeyResolver\KeyResolverInterface;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension;

class ElasticsearchExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerConfiguration($container, $config, $loader);
    }



    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder     $container
     * @param array<array>                                                $config
     * @param \Symfony\Component\DependencyInjection\Loader\XmlFileLoader $loader
     * @throws \Exception
     * @phpstan-ignore-next-line
     */
    private function registerConfiguration(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $container->setParameter('elasticsearch.indexPrefix', $config['indexPrefix']);
        $container->setParameter('elasticsearch.mappings', $config['mappings']);
        $container->setParameter('elasticsearch.kibana', $config['kibana']);
        $container->setParameter('elasticsearch.cache', $config['cache']);

        $driverDefinition = match ($config['driver']['type']) {
            'attributes' => $container->register('elasticsearch.esDriver', AnnotationDriver::class),
            'json' => $container->register('elasticsearch.esDriver', JsonDriver::class),
            default => throw new RuntimeException('ES driver not found.'),
        };
        $driverDefinition->addTag('elasticsearch.esDriver');
        $container->setDefinition('elasticsearch.esDriver', $driverDefinition);

        $container->registerForAutoconfiguration(KeyResolverInterface::class)
            ->addTag('elasticsearch.key_resolver');
        $container->registerForAutoconfiguration(PostEventInterface::class)
            ->addTag('elasticsearch.post_event');
        $container->registerForAutoconfiguration(DocumentBuilderFactoryInterface::class)
            ->addTag('elasticsearch.document_builder_factory');

        $loader->load('elasticsearch.xml');

        $this->configureConnection($container, $config, $loader);

        $defaultDocumentBuilder = new Definition(DefaultDocumentBuilderFactory::class, []);
        $documentFactory = $container->getDefinition('elasticsearch.documentFactory');
        $documentFactory->addMethodCall('addBuilderFactory', [$defaultDocumentBuilder]);

        if ($config['cache']) {
            /** @var string $adapter */
            $adapter = $config['cache'];
            $definition = new ChildDefinition($adapter);
            $container->getDefinition('elasticsearch.mappingMetadataFactory')->replaceArgument(2, $definition);
        }

        if ($this->hasConsole()) {
            $loader->load('console.xml');
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

    protected function hasConsole(): bool
    {
        return class_exists(Application::class);
    }

    /**
     * @param array<array> $config
     * @phpstan-ignore-next-line
     * @throws \Exception
     */
    private function configureConnection(ContainerBuilder $container, array $config, XmlFileLoader $loader): void
    {
        $connectionFactory = $container->getDefinition('elasticsearch.connection_factory');

        $container->setParameter('elasticsearch.hosts', $config['connection']['hosts']);

        if (isset($config['connection']['password']) || isset($config['connection']['username'])) {
            $username = $config['connection']['username'] ?? '';
            $password = $config['connection']['password'] ?? '';
            $connectionFactory->addMethodCall('setBasicAuthentication', [$username, $password]);
        }

        if (isset($config['connection']['cloudId'])) {
            $connectionFactory->addMethodCall('setElasticCloudId', [$config['connection']['cloudId']]);
        }

        if (isset($config['connection']['retries'])) {
            $connectionFactory->addMethodCall('setRetries', [$config['connection']['retries']]);
        }

        if (isset($config['connection']['elasticMetaHeader'])) {
            $connectionFactory->addMethodCall('setElasticMetaHeader', [$config['connection']['elasticMetaHeader']]);
        }

        if (isset($config['connection']['logger'])) {
            $connectionFactory->addMethodCall('setLogger', [new Reference(ltrim($config['connection']['logger'], '@'))]);
        }

        if (isset($config['connection']['httpClient'])) {
            $connectionFactory->addMethodCall('setHttpClient', [new Reference(ltrim($config['connection']['httpClient'], '@'))]);
        }

        if (isset($config['connection']['asyncHttpClient'])) {
            $connectionFactory->addMethodCall('setAsyncHttpClient', [new Reference(ltrim($config['connection']['asyncHttpClient'], '@'))]);
        }

        if (isset($config['connection']['nodePool'])) {
            $connectionFactory->addMethodCall('setNodePool', [new Reference(ltrim($config['connection']['nodePool'], '@'))]);
        }

        if (isset($config['connection']['httpClientOptions'])) {
            $connectionFactory->addMethodCall('setHttpClientOptions', [$config['connection']['httpClientOptions']]);
        }

        if (isset($config['connection']['api'])) {
            $id = $config['connection']['api']['id'] ?? null;
            $apiKey = $config['connection']['api']['apiKey'] ?? null;
            if (null === $apiKey) {
                throw new \Symfony\Component\DependencyInjection\Exception\RuntimeException('Please set apiKey".');
            }
            $connectionFactory->addMethodCall('setApiKey', [$apiKey, $id]);
        }

        if (isset($config['connection']['ssl'])) {
            if (isset($config['connection']['ssl']['sslCA'])) {
                $connectionFactory->addMethodCall('setCABundle', [$config['connection']['ssl']['sslCA']]);
            }
            if (isset($config['connection']['ssl']['sslCert'])) {
                $cert = $config['connection']['ssl']['sslCert']['cert'];
                $password = $config['connection']['ssl']['sslCert']['password'] ?? null;
                if (null === $cert) {
                    throw new \Symfony\Component\DependencyInjection\Exception\RuntimeException('Please set ssl cert".');
                }
                if (null !== $password) {
                    $connectionFactory->addMethodCall('setSSLCert', [$cert, $password]);
                } else {
                    $connectionFactory->addMethodCall('setSSLCert', [$cert]);
                }
            }
            if (isset($config['connection']['ssl']['sslKey'])) {
                $key = $config['connection']['ssl']['sslKey']['key'];
                $password = $config['connection']['ssl']['sslKey']['password'] ?? null;
                if (null === $key) {
                    throw new \Symfony\Component\DependencyInjection\Exception\RuntimeException('Please set ssl key".');
                }
                if (null !== $password) {
                    $connectionFactory->addMethodCall('setSSLKey', [$key, $password]);
                } else {
                    $connectionFactory->addMethodCall('setSSLKey', [$key]);
                }
            }
        }

        if (isset($config['profiling']) && $config['profiling'] && $container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $loader->load('data_collector.xml');
            $loader->load('debug.xml');
        }
    }
}
