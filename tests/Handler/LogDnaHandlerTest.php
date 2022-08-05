<?php
namespace Fusions\Test\Monolog\LogDna\Handler;

use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Fusions\Monolog\LogDna\Handler\LogDnaHandler;
use Fusions\Test\Monolog\LogDna\TestHelperTrait;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\{Client, HandlerStack};
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @coversDefaultClass \Fusions\Monolog\LogDna\Handler\LogDnaHandler
 */
class LogDnaHandlerTest extends TestCase
{
    use TestHelperTrait;

    /**
     * @covers ::setFormatter
     * @covers ::setHttpClient
     * @covers ::setIpAddress
     * @covers ::setMacAddress
     * @covers ::setTags
     * @covers ::getLastBody
     * @covers ::getLastResponse
     */
    public function test_write(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{ "status": "ok" }'),
        ]);

        $mockHttpClient = new Client([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $handler = new LogDnaHandler('test', 'test');
        $handler->setHttpClient($mockHttpClient);
        $handler->setIpAddress('127.0.0.1');
        $handler->setMacAddress('A1-B2-C3-D4-E5-C6');
        $handler->setTags(['FOO', 'BAR']);

        $logger = new Logger('test');
        $logger->pushHandler($handler);
        $logger->info('This is a test message', ['exception' => $this->getExceptionWithStackTrace()]);

        $response = $handler->getLastResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{ "status": "ok" }', $response->getBody()->getContents());
        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . '/../fixtures/logdna-body.json', $handler->getLastBody()
        );
    }
}
