<?php
namespace Fusions\Test\Monolog\LogDna\Formatter;

use Fusions\Monolog\LogDna\Formatter\SmartJsonFormatter;
use Fusions\Test\Monolog\LogDna\TestHelperTrait;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class SmartJsonFormatterTest extends TestCase
{
    use TestHelperTrait;

    public function test_format(): void
    {
        $record = $this->getRecord(Logger::INFO, 'This is a test message', [
            'exception' => $this->getExceptionWithStackTrace('This is a test exception', 42, null),
        ]);

        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . '/../fixtures/smart-json-formatter-format.json',
            (new SmartJsonFormatter)->format($record)
        );
    }

    public function test_format_ignore_paths(): void
    {
        $excludedPath = '/my/fake/path/vendor';

        $record = $this->getRecord(Logger::INFO, 'This is a test message', [
            'exception' => $this->getExceptionWithStackTrace('This is a test exception', 42, null),
        ]);

        $formatter = new SmartJsonFormatter;
        $formatter->setIgnorePaths([$excludedPath]);

        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . '/../fixtures/smart-json-formatter-ignore-path-format.json',
            $formatter->format($record)
        );
    }
}
