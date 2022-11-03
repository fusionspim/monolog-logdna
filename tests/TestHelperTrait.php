<?php

namespace Fusions\Test\Monolog\LogDna;

use Exception;
use stdClass;
use Throwable;

trait TestHelperTrait
{
    public function getExceptionWithStackTrace(string $message = '', int $code = 0, ?Throwable $previous = null, ?array $trace = null): Exception
    {
        return new StackTraceTestException($message, $code, $previous, $trace ?? [
            [
                'class'    => 'MyClass',
                'function' => 'baz',
                'args'     => [true, false, 42, 42.42, 'FOO', ['FOO', 'BAR'], new stdClass],
                'type'     => '->',
                'file'     => '/my/fake/path/src/MyClass.php',
                'line'     => 256,
            ],
            [
                'class'    => 'MyNestedClass',
                'function' => 'bar',
                'args'     => ['FOO', 'BAR'],
                'type'     => '::',
                'file'     => '/my/fake/path/src/Nested/MyNestedClass.php',
                'line'     => 512,
            ],
            [
                'class'    => 'MyVendorClass',
                'function' => 'foo',
                'args'     => [],
                'type'     => '->',
                'file'     => '/my/fake/path/vendor/MyVendorClass.php',
                'line'     => 123,
            ],
            [
                'class'    => '',
                'args'     => [],
                'function' => 'require',
                'file'     => '/my/fake/path/require.php',
                'line'     => 42,
            ],
        ]);
    }
}
