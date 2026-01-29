<?php

declare(strict_types=1);

use Elasticsearch\Bundle\Collector\QueryCollector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('elasticsearch.data_collector.request', QueryCollector::class)
        ->private()
        ->args([
            service('elasticsearch.debugDataHolder'),
            service('elasticsearch.mappingMetadataProvider'),
            service('elasticsearch.connection'),
            '%elasticsearch.kibana%',
        ])
        ->tag('data_collector', [
            'template' => '@Elasticsearch/DataCollector/request.html.twig',
            'id' => 'elasticsearch.data_collector.request',
            'priority' => 334,
        ])
    ;
};

