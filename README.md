# Mezmo/LogDNA handler for Monolog

**This package is now archived, please see https://github.com/nvanheuverzwijn/monolog-logdna**

This library provides a simple Monolog handler for [Mezmo](https://www.mezmo.com/) (previously LogDNA) [ingestion API](https://docs.mezmo.com/log-analysis-api/ref#ingest). 

## Compatibility

This package aims to stay compatible with the latest version of Laravel. The current version supports:

* Laravel 10
* PHP 8.1
* Monolog 3

## Installation

```
composer require fusionspim/monolog-logdna
```

## Basic Usage

```
use Fusions\Monolog\LogDna\Handler\LogDnaHandler;
use Monolog\Logger;

$handler = new LogDnaHandler(getenv('LOGDNA_INGESTION_KEY'), 'host');

$logger = new Logger('app');
$logger->pushHandler($handler);

$logger->info("Don't forget to pack a towel", ['extra' => 'data']);
```

## Advanced Usage

Setter methods are provider on the handler for the `mac`, `ip` and `tag` fields of the ingestion API:

```
$handler->setIpAddress('127.0.0.1');
$handler->setMacAddress('A1-B2-C3-D4-E5-C6');
$handler->setTags(['FOO', 'BAR']);
```

The handler uses Guzzle's [HTTP Client](http://docs.guzzlephp.org/en/stable/) and is configured with a timeout of `5` seconds. You can set your own custom HTTP client if required:

```
$handler->setHttpClient(new Client([
    'timeout' => 60,
    // Your options...
]));
```

You can also get access to the last response received from the LogDNA API:
```
$response = $handler->getLastResponse();
```
