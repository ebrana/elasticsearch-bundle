<?php

declare(strict_types=1);

use Elasticsearch\Bundle\Command\CreateIndexCommand;
use Elasticsearch\Bundle\Command\DeleteIndexCommand;
use Elasticsearch\Bundle\Command\InformationIndexCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('console.command.elasticsearch_create_index', CreateIndexCommand::class)
        ->private()
        ->args([
            service('elasticsearch.connection'),
            service('elasticsearch.mappingMetadataProvider'),
            service('elasticsearch.metadataRequestFactory'),
        ])
        ->tag('console.command')
    ;

    $services->set('console.command.elasticsearch_delete_index', DeleteIndexCommand::class)
        ->private()
        ->args([
            service('elasticsearch.connection'),
            service('elasticsearch.mappingMetadataProvider'),
        ])
        ->tag('console.command')
    ;

    $services->set('console.command.elasticsearch_info_index', InformationIndexCommand::class)
        ->private()
        ->args([
            service('elasticsearch.mappingMetadataProvider'),
            '%elasticsearch.indexPrefix%',
        ])
        ->tag('console.command')
    ;
};
