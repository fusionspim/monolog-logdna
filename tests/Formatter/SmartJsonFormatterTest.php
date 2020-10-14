<?php
namespace Fusions\Test\Monolog\LogDna\Formatter;

use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Fusions\Test\Monolog\LogDna\TestHelperTrait;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter
 */
class SmartJsonFormatterTest extends TestCase
{
    use TestHelperTrait;

    /**
     * @covers ::format
     */
    public function test_format(): void
    {
        $record = $this->getRecord(Logger::INFO, 'This is a test message', [
            'exception' => $this->getExceptionWithStackTrace('This is a test exception', 42, null),
        ]);

        $output = json_decode((new SmartJsonFormatter)->format($record), true);

        // Core fields.
        $this->assertSame($record['datetime']->getTimestamp(), $output['lines'][0]['timestamp']);
        $this->assertSame($record['message'], $output['lines'][0]['line']);
        $this->assertSame($record['channel'], $output['lines'][0]['app']);
        $this->assertSame($record['level_name'], $output['lines'][0]['level']);

        // Meta field.
        $this->assertSame(get_class($record['context']['exception']), $output['lines'][0]['meta']['exception']['class']);
        $this->assertSame($record['context']['exception']->getMessage(), $output['lines'][0]['meta']['exception']['message']);
        $this->assertSame($record['context']['exception']->getCode(), $output['lines'][0]['meta']['exception']['code']);

        // Trace fields.
        $this->assertSame('MyClass', $output['lines'][0]['meta']['exception']['trace'][0]['class']);
        $this->assertSame('method', $output['lines'][0]['meta']['exception']['trace'][0]['type']);
        $this->assertSame('/my/fake/path/src/MyClass.php', $output['lines'][0]['meta']['exception']['trace'][0]['file']);
        $this->assertSame(256, $output['lines'][0]['meta']['exception']['trace'][0]['line']);
        $this->assertSame(
            [
                'bool(true)',
                'bool(false)',
                'int(42)',
                'float(42.42)',
                'string(FOO)',
                'array([string(FOO), string(BAR)]), 2',
                'stdClass',
            ],
            $output['lines'][0]['meta']['exception']['trace'][0]['args']
        );

        $this->assertSame('MyNestedClass', $output['lines'][0]['meta']['exception']['trace'][1]['class']);
        $this->assertSame('static', $output['lines'][0]['meta']['exception']['trace'][1]['type']);
        $this->assertSame('/my/fake/path/src/Nested/MyNestedClass.php', $output['lines'][0]['meta']['exception']['trace'][1]['file']);
        $this->assertSame(512, $output['lines'][0]['meta']['exception']['trace'][1]['line']);
        $this->assertSame(['string(FOO)', 'string(BAR)'], $output['lines'][0]['meta']['exception']['trace'][1]['args']);

        $this->assertSame('MyVendorClass', $output['lines'][0]['meta']['exception']['trace'][2]['class']);
        $this->assertSame('method', $output['lines'][0]['meta']['exception']['trace'][2]['type']);
        $this->assertSame('/my/fake/path/vendor/MyVendorClass.php', $output['lines'][0]['meta']['exception']['trace'][2]['file']);
        $this->assertSame(123, $output['lines'][0]['meta']['exception']['trace'][2]['line']);
        $this->assertSame([], $output['lines'][0]['meta']['exception']['trace'][2]['args']);

        $this->assertSame('', $output['lines'][0]['meta']['exception']['trace'][3]['class']);
        $this->assertSame('require', $output['lines'][0]['meta']['exception']['trace'][3]['function']);
        $this->assertSame('function', $output['lines'][0]['meta']['exception']['trace'][3]['type']);
        $this->assertSame('/my/fake/path/require.php', $output['lines'][0]['meta']['exception']['trace'][3]['file']);
        $this->assertSame(42, $output['lines'][0]['meta']['exception']['trace'][3]['line']);
        $this->assertSame([], $output['lines'][0]['meta']['exception']['trace'][3]['args']);
    }

    /**
     * @covers ::format
     * @covers ::setIgnorePaths
     */
    public function test_format_ignore_paths(): void
    {
        $excludedPath = '/my/fake/path/vendor';

        $record = $this->getRecord(Logger::INFO, 'This is a test message', [
            'exception' => $this->getExceptionWithStackTrace('This is a test exception', 42, null),
        ]);

        $formatter = new SmartJsonFormatter;
        $formatter->setIgnorePaths([$excludedPath]);
        $output = json_decode($formatter->format($record), true);

        $this->assertCount(3, $output['lines'][0]['meta']['exception']['trace']);

        foreach ($output['lines'][0]['meta']['exception']['trace'] as $trace) {
            $this->assertStringNotContainsString($excludedPath, $trace['file']);
        }
    }
}
