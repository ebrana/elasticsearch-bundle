<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\Twig;

use LZCompressor\LZString;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use JsonException;

class ElasticsearchTwigExtension extends AbstractExtension
{
    /**
     * Define our functions
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('elasticsearch_kibana_query', [$this, 'kibanaQuery'], ['is_safe' => ['html'], 'deprecated' => false]),
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
}
