# Elasticsearch Bundle
Elasticsearch Symfony bundle pro balíček https://github.com/ebrana/elasticsearch.

### Instalace
````
composer require ebrana/elasticsearch-bundle
````

#### Konfigurace

````
elasticsearch:
    profiling: true
    indexPrefix: "katalog_"
    # tato sekce může být vynechána, protže attributes je default driver
    #    driver:
    #        type: "attributes" # attributes nebo json
    #        keyResolver: Elasticsearch\Bundle\KeyResolver # resolvuje klíče typu nested nebo object
    mappings:
        - App\Entity\Elasticsearch\Product
    hosts:
        - '%env(resolve:ELASTICSEARCH_URL)%'
````
