# LogDNA handler for Monolog

This library provides a simple Monolog handler for [LogDNA's](https://logdna.com/) [ingestion API](https://docs.logdna.com/reference#logsingest). It also provides a smart JSON formatter that can help correctly format complex stack traces.

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

The handler uses Symfony's [HTTP Client](https://symfony.com/doc/master/components/http_client.html) and is configured with a timeout of `5` seconds. You can set your own custom HTTP client if required:

```
$handler->setHttpClient(HttpClient::create([
    'timeout' => 60,
    // Your options...
]));
```

You can also get access to the last response received from the LogDNA API:
```
$response = $handler->getLastResponse();
```

## Smart JSON Formatter

The smart JSON formatter helps format complex stack traces, keeping them under LogDNA's 32Kb limit:

```
use Fusions\Monolog\LogDna\Handler\LogDnaHandler;
use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Monolog\Logger;

$handler = new LogDnaHandler(getenv('LOGDNA_INGESTION_KEY'), 'host');
$handler->setFormatter(new SmartJsonFormatter);
```

It can be configured to exclude specific paths from stack traces. You can use this to exclude vendor components:

```
$formatter = new SmartJsonFormatter;
$formatter->setIgnorePaths(['/path/to/vendor']);
```
