# keboola/openlineage-generator

This library will generate jobs metadata obtained from [Job Queue API open-api-lineage endpoint](https://app.swaggerhub.com/apis-docs/keboola/job-queue-api/1.2.6#/Jobs/getJobOpenApiLineage) for a data lineage service supporting OpenLineage API (e.g. Marquez).

## Installation

The library is available as [composer package](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).
To start using this library in your project follow these steps:

**Install package:**

```bash
composer require keboola/openlineage-generator
```


**Add autoloader in your bootstrap script:**

```bash
require 'vendor/autoload.php';
```

Read more in [Composer documentation](http://getcomposer.org/doc/01-basic-usage.md)

## Usage

```php
$openLineageWriter = new OpenLineageWriter(
            $queueClient, // keboola job queue client
            $openLineageClient, // http client for open lineage api
            $logger,
            $createdTimeFrom, //DateTimeImmutablee object from which jobs will be imported
            $config->getOpenLineageEndpoint(), // url of open lineage api
            $config->getJobNameAsConfig() // boolean, if true, job name will be used as config name
        );

$openLineageWriter->write();
```

## Development

Clone this repository and init the workspace with following command:

```bash
git clone https://github.com/keboola/openlineage-generator
cd openlineage-generator
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the CI checks using this command:

```bash
docker-compose run --rm dev composer ci
```

# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/)

## License

MIT licensed, see [LICENSE](./LICENSE) file.
