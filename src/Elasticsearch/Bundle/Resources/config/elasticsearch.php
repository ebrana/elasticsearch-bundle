<?php

declare(strict_types=1);

use Elasticsearch\Mapping\MappingMetadataProvider;
use Elasticsearch\Mapping\MappingMetadataFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('elasticsearch.esClientBuilder', Elastic\Elasticsearch\ClientBuilder::class);

    $services->set('elasticsearch.connection_factory')
        ->class(Elastic\Elasticsearch\ClientBuilder::class)
        ->factory([service('elasticsearch.esClientBuilder'), 'create'])
        ->call('setHosts', ['%elasticsearch.hosts%'])
    ;

    $services->set('elasticsearch.connection', Elasticsearch\Connection\Connection::class)
        ->private()
        ->args([
            service('elasticsearch.connection_factory'),
            '%elasticsearch.indexPrefix%'
        ])
    ;

    $services->set('elasticsearch.mappingMetadataFactory', MappingMetadataFactory::class)
        ->private()
        ->args([
            service('elasticsearch.esDriver'),
            '%elasticsearch.mappings%',
            '%elasticsearch.cache%',
        ])
    ;

    $services->set('elasticsearch.mappingMetadataProvider', MappingMetadataProvider::class)
        ->private()
        ->args([
            service('elasticsearch.mappingMetadataFactory'),
        ])
    ;

    $services->alias(MappingMetadataProvider::class, 'elasticsearch.mappingMetadataProvider')
        ->private()
    ;

    $services->alias(Elasticsearch\Connection\Connection::class, 'elasticsearch.connection')
        ->private()
    ;

    $services->set('elasticsearch.metadataRequestFactory', Elasticsearch\Mapping\Request\MetadataRequestFactory::class)
        ->private()
        ->alias(Elasticsearch\Mapping\Request\MetadataRequestFactory::class, 'elasticsearch.metadataRequestFactory')
    ;

    $services->set('elasticsearch.searchBuilderFactory', Elasticsearch\Search\SearchBuilderFactory::class)
        ->private()
        ->args([
            service('elasticsearch.mappingMetadataProvider'),
            '%elasticsearch.indexPrefix%',
        ])
        ->alias(Elasticsearch\Search\SearchBuilderFactory::class, 'elasticsearch.searchBuilderFactory')
    ;

    $services->set('elasticsearch.documentFactory', Elasticsearch\Indexing\DocumentFactory::class)
        ->private()
        ->args([
            service('elasticsearch.mappingMetadataProvider'),
        ])
        ->alias(Elasticsearch\Indexing\DocumentFactory::class, 'elasticsearch.documentFactory')
    ;
};
