<?php

declare(strict_types=1);

use Elasticsearch\Bundle\Controller\ProfilerPlaygroundController;
use Elasticsearch\Bundle\Profiler\PlaygroundService;
use Elasticsearch\Bundle\Twig\ElasticsearchTwigExtension;
use Elasticsearch\Debug\Connection as DebugConnection;
use Elasticsearch\Debug\DebugDataHolder;
use Elasticsearch\Tools\PhpQueryBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('elasticsearch.debugDataHolder', DebugDataHolder::class);

    $services->set('elasticsearch.connection', DebugConnection::class)
        ->private()
        ->args([
            service('elasticsearch.debugDataHolder'),
            service('elasticsearch.connection_factory'),
            '%elasticsearch.indexPrefix%',
        ])
    ;

    $services->set('elasticsearch.twig.elasticsearch_extension', ElasticsearchTwigExtension::class)
        ->private()
        ->tag('twig.extension')
    ;

    $services->set('elasticsearch.tools.php_query_builder', PhpQueryBuilder::class)
        ->private()
    ;

    $services->set('elasticsearch.profiler.playground', PlaygroundService::class)
        ->private()
        ->args([
            service('elasticsearch.connection'),
            service('elasticsearch.mappingMetadataProvider'),
            service('elasticsearch.tools.php_query_builder'),
        ])
    ;

    $services->set(ProfilerPlaygroundController::class)
        ->public()
        ->args([
            service('elasticsearch.profiler.playground'),
            service(CsrfTokenManagerInterface::class),
            '%kernel.debug%',
        ])
        ->tag('controller.service_arguments')
    ;
};
