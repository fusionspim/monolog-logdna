# LogDNA handler for Monolog

This library provides a simple Monolog handler for [LogDNA's](https://logdna.com/) [ingestion API](https://docs.logdna.com/reference#logsingest). It also provides a smart JSON formatter that can help correctly format complex stack traces.

We wrote this library as an alternative to existing solutions because it provides more flexibility (e.g. control over the HTTP client), and it allowed us to open source our bespoke JSON formatter specifically designed for LogDNA. 

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

## Smart JSON Formatter

The smart JSON formatter helps format complex stack traces, keeping them under LogDNA's [32KB limit](https://github.com/logdna/nodejs/blob/master/README.md#line):

```
use Fusions\Monolog\LogDna\Handler\LogDnaHandler;
use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Monolog\Logger;

$handler = new LogDnaHandler(getenv('LOGDNA_INGESTION_KEY'), 'host');
$handler->setFormatter(new SmartJsonFormatter);
```

### Stack Trace Mapping And Filtering

The smart JSON formatter can be configured to map and filter the final stack trace. This can be useful when you want to modify (map) or remove frames (filter) from the final stack trace. 

You can add maps and filters using `addMap()` and `addFilter()`. Each accepts a callable that is applied to every frame in the stack trace. 

Maps are applied first and should return an array. Filters are applied last and should return a boolean. Both are applied in the order they were added to the formatter.

```
use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;

$formatter = new SmartJsonFormatter;

// Modify the stack trace frame to include an extra field.
$formatter->addMap(function (array $frame): array {
    $frame['foo'] = 'bar';
    return $frame;
});

// Remove all stack trace frames with zero arguments.
$formatter->addFilter(fn (array $frame): bool => count($frame['args']) > 0);
```

An `IgnorePathsFilter` class is included which removes specific paths from stack traces. You can use this to exclude vendor or middleware components from your stack trace:
```
use Fusions\Monolog\LogDna\Filter\IgnorePathsFilter;
use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;

$formatter = new SmartJsonFormatter;
$formatter->addFilter(new use IgnorePathsFilter(['/path/to/vendor']));
```

A `RedactArgumentsMap` class is also included which redacts sensitive arguments from matching frames. You can use this to redact database credentials from your stack trace:
```
use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Fusions\Monolog\LogDna\Map\RedactArgumentsMap;

$formatter = new SmartJsonFormatter;
$formatter->addMap(new RedactArgumentsMap([
    [
        'class'    => 'PDO', 
        'function' => '__construct', 
        'type'     => 'method'
    ]
]));
```
