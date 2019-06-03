<?php
namespace Fusions\Test\Monolog\LogDna\Handler;

use DateTime;
use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Fusions\Monolog\LogDna\Handler\LogDnaHandler;
use Fusions\Test\Monolog\LogDna\TestHelperTrait;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class LogDnaHandlerTest extends TestCase
{
    use TestHelperTrait;

    public function testWrite()
    {
        $mockHttpClient = new MockHttpClient([
            new MockResponse('{ "status": "ok" }', ['http_code' => 200])
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
        $this->assertSame(['status' => 'ok'], $response->toArray());
        $this->assertSame('POST', $response->getInfo('http_method'));
        $this->assertStringStartsWith('https://logs.logdna.com/logs/ingest', $response->getInfo('url'));

        // Request Headers.
        $request = $response->getRequestOptions();
        $this->assertSame('application/json', $request['headers']['content-type'][0]);
        $this->assertSame('Basic dGVzdA==', $request['headers']['authorization'][0]);

        // Request Query String.
        $this->assertSame('test', $request['query']['hostname']);
        $this->assertSame('A1-B2-C3-D4-E5-C6', $request['query']['mac']);
        $this->assertSame('127.0.0.1', $request['query']['ip']);
        $this->assertSame(['FOO', 'BAR'], $request['query']['tags']);

        // Request Body.
        $body = json_decode($request['body'], true);
        $body['lines'][0]['timestamp'] = (new DateTime('2019-01-01 01:01:01'))->getTimestamp(); // Hard code timestamp.

        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . '/../fixtures/log_lines.json',
            json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
