<?php

declare(strict_types=1);

use Elasticsearch\Bundle\Controller\ProfilerPlaygroundController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('elasticsearch_profiler_playground_generate', '/_profiler/ebrana/elasticsearch/playground/generate')
        ->controller([ProfilerPlaygroundController::class, 'generate'])
        ->methods(['POST']);

    $routes
        ->add('elasticsearch_profiler_playground_execute', '/_profiler/ebrana/elasticsearch/playground/execute')
        ->controller([ProfilerPlaygroundController::class, 'execute'])
        ->methods(['POST']);
};
