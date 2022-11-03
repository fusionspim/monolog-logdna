<?php

namespace Fusions\Test\Monolog\LogDna;

use Exception;
use ReflectionClass;
use Throwable;

/**
 * This is a horrible hacky exception that you can set a custom trace on.
 * Don't try this at home kids.
 */
class StackTraceTestException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable|null $previous = null, array $trace = [])
    {
        parent::__construct($message, $code, $previous);
        $this->setTrace($trace);
    }

    protected function setTrace(array $trace): void
    {
        $reflection = new ReflectionClass(get_parent_class($this));
        $property   = $reflection->getProperty('trace');
        $property->setAccessible(true);
        $property->setValue($this, $trace);
    }
}
