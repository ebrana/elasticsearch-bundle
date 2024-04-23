<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\Collector;

use Elasticsearch\Debug\DebugDataHolder;
use Elasticsearch\Mapping\Exceptions\MappingJsonCreateException;
use Elasticsearch\Mapping\MappingMetadataProvider;
use Elasticsearch\Mapping\Request\MetadataRequestFactory;
use Override;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QueryCollector extends AbstractDataCollector
{
    private ?int $invalidEntityCount = null;
    private array $data = [];

    public function __construct(
        private readonly DebugDataHolder $debugDataHolder,
        private readonly MappingMetadataProvider $mappingMetadataProvider,
        private readonly string $kibana,
    ) {
    }

    #[Override]
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = [
            'queries'    => $this->debugDataHolder->getData(),
            'entities'   => $this->provideEntitiesMapping(),
            'kibana'     => $this->kibana,
            'connection' => [
                'default' => 'elasticsearch.connection',
            ],
        ];
    }

    public function getName(): string
    {
        return 'elasticsearch.data_collector.request';
    }

    /**
     * @return \Elasticsearch\Debug\Query[]
     */
    public function getQueries(): array
    {
        return $this->data['queries'] ?? [];
    }

    public function getQueryCount(): int
    {
        return count($this->data['queries']);
    }

    /**
     * @return array<string, array<string, array<bool|string|int>>>
     */
    public function getEntities(): array
    {
        return $this->data['entities'];
    }

    public function getTime(): float
    {
        $time = 0;
        foreach ($this->data['queries'] as $query) {
            $time += $query['executionMS'];
        }

        return $time;
    }

    public function getKibana(): string
    {
        return $this->data['kibana'];
    }

    /**
     * @return string[]
     */
    public function getConnection(): array
    {
        return $this->data['connection'];
    }

    public function getInvalidEntityCount(): int
    {
        return $this->invalidEntityCount ??= count($this->data['entities']['invalid']);
    }

    /**
     * @return array<string, array<string, array<bool|string|int>>>
     */
    private function provideEntitiesMapping(): array
    {
        $data = [
            'classes' => [],
        ];
        $data['invalid'] = [];
        $mappings = $this->mappingMetadataProvider->getMappingMetadata();

        foreach ($mappings->getMetadata() as $class => $index) {
            try {
                if (false === class_exists($class)) {
                    throw new ReflectionException(sprintf('Class "%s" not exists or cannot loadable.', $class));
                }
                $reflection = new ReflectionClass($class);
            } catch (ReflectionException $e) {
                $data['invalid'][$class] = [
                    'file'    => '',
                    'line'    => '',
                    'message' => $e->getMessage(),
                ];
                continue;
            }
            $metadataRequestFactory = new MetadataRequestFactory();
            try {
                $metadataRequest = $metadataRequestFactory->create($index);

                $data['classes'][$class] = [
                    'body' => $metadataRequest->getMappingJson(),
                    'file' => $reflection->getFileName(),
                    'line' => $reflection->getStartLine(),
                ];
            } catch (MappingJsonCreateException $e) {
                $data['invalid'][$class] = [
                    'file'    => $reflection->getFileName(),
                    'line'    => $reflection->getStartLine(),
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $data;
    }
}
