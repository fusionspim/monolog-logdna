<?php
namespace Fusions\Test\Monolog\LogDna\Formatter;

use Fusions\Monolog\LogDna\Formatter\BasicJsonFormatter;
use Fusions\Test\Monolog\LogDna\TestRecordTrait;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class BasicJsonFormatterTest extends TestCase
{
    use TestRecordTrait;

    public function testFormat(): void
    {
        $record = $this->getRecord(Logger::INFO, 'This is a test message', ['FOO' => 'BAR']);

        $this->assertSame(
            json_encode([
                'lines' => [
                    [
                        'timestamp' => $record['datetime']->getTimestamp(),
                        'line'      => $record['message'],
                        'app'       => $record['channel'],
                        'level'     => $record['level_name'],
                        'meta'      => $record['context'],
                    ]
                ]
            ]),
            trim((new BasicJsonFormatter)->format($record))
        );
    }
}
