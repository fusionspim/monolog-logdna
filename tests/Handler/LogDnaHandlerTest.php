<?php
namespace Fusions\Test\Monolog\LogDna\Handler;

use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Fusions\Monolog\LogDna\Handler\LogDnaHandler;
use Fusions\Test\Monolog\LogDna\TestHelperTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
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
     * @covers ::getLastBody
     * @covers ::getLastResponse
     * @covers ::setFormatter
     * @covers ::setHttpClient
     * @covers ::setIpAddress
     * @covers ::setMacAddress
     * @covers ::setTags
     */
    public function test_write(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{ "status": "ok" }'),
        ]);

        $mockHttpClient = new Client([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $formatter = new SmartJsonFormatter;

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
        $this->assertJsonFileEqualsJsonStringIgnoring(
            __DIR__ . '/../fixtures/logdna-body.json',
            $handler->getLastBody(),
            ['timestamp', 'file']
        );
    }

    /**
     * @covers ::getLastBody
     * @covers ::getLastResponse
     * @covers ::setFormatter
     * @covers ::setHttpClient
     * @covers ::setIpAddress
     * @covers ::setMacAddress
     * @covers ::setTags
     */
    public function test_shorten_long_exceptions(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{ "status": "ok" }'),
        ]);

        $mockHttpClient = new Client([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $formatter = new SmartJsonFormatter;

        $handler = new LogDnaHandler('test', 'test');
        $handler->setHttpClient($mockHttpClient);
        $handler->setFormatter($formatter);
        $handler->setIpAddress('127.0.0.1');
        $handler->setMacAddress('A1-B2-C3-D4-E5-C6');
        $handler->setTags(['FOO', 'BAR']);

        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $longTrace = [];

        for ($i = 0; $i < 250; $i++) {
            $longTrace[] = [
                'class'    => 'MyClass',
                'function' => 'baz',
                'args'     => [true, false, 42, 42.42, 'FOO', ['FOO', 'BAR'], new stdClass],
                'type'     => '->',
                'file'     => '/my/fake/path/src/MyClass.php',
                'line'     => 256,
            ];
        }

        $logger->info('This is a test message', [
            'exception' => $this->getExceptionWithStackTrace('This is a test exception', 42, null, $longTrace),
        ]);

        // Response.
        $response = $handler->getLastResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{ "status": "ok" }', $response->getBody()->getContents());
        $this->assertJsonFileEqualsJsonStringIgnoring(
            __DIR__ . '/../fixtures/long-logdna-body.json',
            $handler->getLastBody(),
            ['timestamp', 'file', 'longException']
        );

        $decodedBody = json_decode($handler->getLastBody(), true);
        $this->assertSame(30000, mb_strlen($decodedBody['lines'][0]['meta']['longException'], '8bit'));
    }

    public function assertJsonFileEqualsJsonStringIgnoring(string $expectedFile, string $json, array $ignoring = []): void
    {
        $this->assertJsonStringEqualsJsonString(
            file_get_contents($expectedFile),
            json_encode($this->removeKeys(json_decode($json, true), $ignoring))
        );
    }

    public function removeKeys(array $data, array $keys)
    {
        $newData = [];

        foreach ($data as $key => $item) {
            if (! in_array($key, $keys, true)) {
                $newData[$key] = is_array($item) ? $this->removeKeys($item, $keys) : $item;
            } else {
                $newData[$key] = '--IGNORED--';
            }
        }

        return $newData;
    }
}
