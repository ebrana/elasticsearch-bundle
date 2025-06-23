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
Pro registraci stačí dědit ``DocumentBuilderFactoryInterface`` a zaregistrovat jako service do kontejneru.

#### Key Resolver
ObjectType a NestedType disponuje možností resolvovat názvy fieldů. Pro Annotation driver
je možné si nastavit globálně resolver přes keyResolver atribut (viz. yaml výše).
Pokud z nějakého důvodu je potřeba u property vlastní resolver, tak je to možné udělat takto:


Vytvoříme si Custom resolver jako službu DI kontejneru a implementujeme rozhranní ``KeyResolverInterface``.

a upravíme PHP atribut následovně:
````php
#[NestedType(
   keyResolver: CustomKeyResolver::class,
   fieldsTemplate: new TextType(),
)]
protected array $sellingPrice = [];
````

#### Post Event callback
Pokud potřebuji ještě nějaké dynamické prvky, pak si mohu u attributu index nastavit
postEventClass a zaregistrovat service do kontejneru implementující rozhranní PostEventInterface.
Například:

```php
<?php

declare(strict_types=1);

namespace Elasticsearch\Tests;

use Elasticsearch\Mapping\Drivers\Events\PostEventInterface;
use Elasticsearch\Mapping\Index;
use Elasticsearch\Mapping\Types\Text\TextType;

class PostEventSample implements PostEventInterface
{
    public function postCreateIndex(Index $index): void
    {
        $field = new TextType(name: 'postEventName');
        $field->setFieldName('postEventName');
        $index->addProperty($field);
    }
}
```

#### Profiler
![screen.png](screen.png)
