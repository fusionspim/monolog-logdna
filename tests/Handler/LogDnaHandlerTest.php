<?php
namespace Fusions\Test\Monolog\LogDna\Handler;

use DateTime;
use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Fusions\Monolog\LogDna\Handler\LogDnaHandler;
use Fusions\Test\Monolog\LogDna\TestHelperTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LogDnaHandlerTest extends TestCase
{
    use TestHelperTrait;

    public function testWrite()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{ "status": "ok" }')
        ]);

        $mockHttpClient = new Client([
            'handler' => HandlerStack::create($mockHandler)
        ]);

        $formatter = new SmartJsonFormatter;
        $formatter->includeStacktraces(true);

        $handler = new LogDnaHandler('test', 'test');
        $handler->setHttpClient($mockHttpClient);
        $handler->setFormatter($formatter);
        $handler->setIpAddress('127.0.0.1');
        $handler->setMacAddress('A1-B2-C3-D4-E5-C6');
        $handler->setTags(['FOO', 'BAR']);

        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $logger->info('This is a test message', [
            'exception' => $this->getExceptionWithStackTrace(),
        ]);

        // Response.
        $response = $handler->getLastResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{ "status": "ok" }', $response->getBody()->getContents());
    }
}
