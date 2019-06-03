<?php
namespace Fusions\Test\Monolog\LogDna;

use DateTime;
use Exception;
use Monolog\Logger;
use stdClass;
use Throwable;

trait TestHelperTrait
{
    /**
     * @link https://github.com/Seldaek/monolog/blob/1.x/tests/Monolog/TestCase.php
     */
    public function getRecord(int $level = Logger::WARNING, string $message = 'test', array $context = []): array
    {
        return [
            'message'    => $message,
            'context'    => $context,
            'level'      => $level,
            'level_name' => Logger::getLevelName($level),
            'channel'    => 'test',
            'datetime'   => DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra'      => [],
        ];
    }

    public function getExceptionWithStackTrace(string $message = '', int $code = 0, Throwable $previous = null, array $trace = []): Exception
    {
        if (count($trace) === 0) {
            $trace = [
                [
                    'class' => 'MyClass',
                    'args'  => [true, false, 42, 42.42, 'FOO', ['FOO', 'BAR'], new stdClass],
                    'type'  => '->',
                    'file'  => '/my/fake/path/src/MyClass.php',
                    'line'  => 256,
                ],
                [
                    'class' => 'MyNestedClass',
                    'args'  => ['FOO', 'BAR'],
                    'type'  => '::',
                    'file'  => '/my/fake/path/src/Nested/MyNestedClass.php',
                    'line'  => 512,
                ],
                [
                    'class' => 'MyVendorClass',
                    'args'  => [],
                    'type'  => '->',
                    'file'  => '/my/fake/path/vendor/MyVendorClass.php',
                    'line'  => 123,
                ],
                [
                    'class'    => '',
                    'args'     => [],
                    'function' => 'require',
                    'file'     => '/my/fake/path/require.php',
                    'line'     => 42,
                ]
            ];
        }

        return new StackTraceTestException($message, $code, $previous, $trace);
    }
}
