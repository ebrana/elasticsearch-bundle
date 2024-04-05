<?php

declare(strict_types=1);

use Elasticsearch\Bundle\Command\CreateIndexCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('console.command.elasticsearch_create_index', CreateIndexCommand::class)
            ->args([
                service('elasticsearch.documentFactory'),
            ])
            ->tag('console.command')
    ;
};
