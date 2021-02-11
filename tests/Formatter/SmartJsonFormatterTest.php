<?php
namespace Fusions\Test\Monolog\LogDna\Formatter;

use Fusions\Monolog\LogDna\Filter\IgnorePathsFilter;
use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Fusions\Monolog\LogDna\Map\RedactArgumentsMap;
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
                'array(2)',
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
     * @covers ::addStackTraceModifier
     * @covers ::format
     * @covers ::IgnorePathsFilter
     */
    public function test_format_filter_ignore_paths(): void
    {
        $excludedPath = '/my/fake/path/vendor';

        $record = $this->getRecord(Logger::INFO, 'This is a test message', [
            'exception' => $this->getExceptionWithStackTrace('This is a test exception', 42, null),
        ]);

        $formatter = new SmartJsonFormatter;
        $formatter->addFilter(new IgnorePathsFilter([$excludedPath]));
        $output = json_decode($formatter->format($record), true);

        $this->assertCount(3, $output['lines'][0]['meta']['exception']['trace']);

        foreach ($output['lines'][0]['meta']['exception']['trace'] as $trace) {
            $this->assertStringNotContainsString($excludedPath, $trace['file']);
        }
    }

    /**
     * @covers ::addStackTraceModifier
     * @covers ::format
     * @covers ::RedactArgumentsMap
     */
    public function test_format_map_redact_arguments(): void
    {
        $redactedFrameArguments = [
            'mysql:host=test.database.hostname.com;port=3306;dbname=test',
            'username',
            'password',
            42.42
        ];

        $record = $this->getRecord(Logger::INFO, 'This is a test message containing sensitive credentials', [
            'exception' => $this->getExceptionWithStackTrace('This is a test exception', 42, null, [
                [
                    'class'    => 'PDO',
                    'function' => '__construct',
                    'args'     => [
                        'mysql:host=test.database.hostname.com;port=3306;dbname=test',
                        'username',
                        'password',
                        [2 => true],
                        42,
                        42.42,
                        new \stdClass,
                    ],
                    'type'     => '->',
                    'file'     => '/vendor/database/Connectors/Connector.php',
                    'line'     => 70,
                ],
                [
                    'class'    => 'Database\\Connectors\\Connector',
                    'function' => 'createPdoConnection',
                    'args'     => [
                        'mysql:host=test.database.hostname.com;port=3306;dbname=test',
                        'username',
                        'password',
                        [2 => true],
                    ],
                    'type'     => '->',
                    'file'     => '/vendor/database/Connectors/Connector.php',
                    'line'     => 46,
                ],
                [
                    'class'    => 'Database\\Connectors\\Connector',
                    'function' => 'createConnection',
                    'args'     => [
                        'mysql:host=test.database.hostname.com;port=3306;dbname=test',
                        [
                            'username' => 'username',
                            'password' => 'password',
                        ],
                        [2 => true],
                    ],
                    'type'     => '->',
                    'file'     => '/vendor/database/Connectors/MySqlConnector.php',
                    'line'     => 24,
                ],
                [
                    'function' => 'prepareDatabase',
                    'args'     => [
                        'foo',
                        'bar',
                        [2 => true],
                        42,
                        42.42,
                        new \stdClass,
                    ],
                    'file'     => '/my/fake/path/prepare.php',
                    'line'     => 42,
                ],
                [
                    'function' => 'connectToDatabase',
                    'args'     => [],
                    'file'     => '/my/fake/path/connect.php',
                    'line'     => 42,
                ],
            ]),
        ]);

        $formatter = new SmartJsonFormatter;
        $formatter->addMap(new RedactArgumentsMap($redactedFrameArguments));
        $output = json_decode($formatter->format($record), true);

        $this->assertCount(5, $output['lines'][0]['meta']['exception']['trace']);

        $expectedArguments = [
            [
                'string(***REDACTED***)',
                'string(***REDACTED***)',
                'string(***REDACTED***)',
                'array(1)',
                'int(42)',
                'float(***REDACTED***)',
                'stdClass',
            ],
            [
                'string(***REDACTED***)',
                'string(***REDACTED***)',
                'string(***REDACTED***)',
                'array(1)',
            ],
            [
                'string(***REDACTED***)',
                'array(2)',
                'array(1)',
            ],
            [
                'string(foo)',
                'string(bar)',
                'array(1)',
                'int(42)',
                'float(***REDACTED***)',
                'stdClass',
            ],
            [],
        ];

        foreach ($expectedArguments as $index => $expectedArgument) {
            $this->assertSame($expectedArgument, $output['lines'][0]['meta']['exception']['trace'][$index]['args']);
        }
    }
}
