<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\Twig;

use LZCompressor\LZString;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use JsonException;

class ElasticsearchTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    /**
     * Define our functions
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('elasticsearch_kibana_query', [$this, 'kibanaQuery'], ['is_safe' => ['html'], 'deprecated' => false]),
            new TwigFilter('elasticsearch_pretty_query', [$this, 'prettyPrintJson'], ['is_safe' => ['html'], 'deprecated' => false]),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('elasticsearch_route_exists', [$this, 'routeExists']),
        ];
    }

    /**
     * @param string[] $query
     * @return string
     * @throws JsonException
     * @see https://github.com/elastic/kibana/blob/main/src/plugins/console/README.md (composer require nullpunkt/lz-string-php)
     */
    public function kibanaQuery(array $query): string
    {
        $body = '';
        if (is_string($query['body'])) {
            $body = print_r(json_encode(json_decode($query['body'], false, 512, JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), true);
        }

        return LZString::compressToBase64($query['query'] . PHP_EOL . $body);
    }

    public function prettyPrintJson(string $jsonString)
    {
        return print_r(json_encode(json_decode($jsonString, false, 512, JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), true);
    }

    public function routeExists(string $routeName): bool
    {
        return null !== $this->router->getRouteCollection()->get($routeName);
    }
}
