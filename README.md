# Elasticsearch Bundle
Elasticsearch Symfony Bundle pro balíček https://github.com/ebrana/elasticsearch.

### Instalace
````
composer require ebrana/elasticsearch-bundle
````

#### Konfigurace

````yaml
elasticsearch:
    profiling: true
    indexPrefix: "katalog_"
    # cache je nepovinné (adapters: https://symfony.com/doc/current/components/cache.html#available-cache-adapters)
    cache: 'cache.adapter.filesystem'
    # tato sekce může být vynechána, protože attributes je default driver
    #    driver:
    #        type: "attributes" # attributes nebo json
    #        keyResolver: Elasticsearch\Bundle\KeyResolver # resolvuje klíče typu nested nebo object
    mappings:
        - App\Entity\Elasticsearch\Product
    connection:
        hosts:
            - '%env(resolve:ELASTICSEARCH_URL)%'
        username: ""
        password: ""
        cloudId: ""
        retries: 10
        elasticMetaHeader: true/false
        logger: "@logger" #Psr\Log\LoggerInterface
        httpClient: ... #Psr\Http\Client\ClientInterface
        asyncHttpClient: ... #Http\Client\HttpAsyncClient
        nodePool: ... #Elastic\Transport\NodePool\NodePoolInterface
        httpClientOptions: ... # podle http clienta
        api:
            apiKey: ""
            id: ""
        ssl:
            sslVerification: true/false
            sslCA: ""
            sslCert:
                cert: ""
                password: ""
            sslKey:
                key: ""
                password: ""
        
````

#### Registrace Document Builder Factories
Pro registraci použijte PHP atribut nad třídu builder factory

````php
#[AutoconfigureTag('elasticsearch.document_builder_factory')]
class ProductDocumentBuilderFactory implements DocumentBuilderFactoryInterface
{
   ...
}
````

#### Profiler
![screen.png](screen.png)
