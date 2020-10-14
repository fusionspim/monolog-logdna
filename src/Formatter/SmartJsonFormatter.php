<?php
namespace Fusions\Monolog\LogDna\Formatter;

use Throwable;

class SmartJsonFormatter extends BasicJsonFormatter
{
    protected $includeStacktraces = true;
    private $ignorePaths          = [];

    /**
     * Ignore paths are paths to code that will be excluded from the log stack trace output.
     * This is useful when you have deep stacks (e.g.  middleware) that isn't relevant to the log output.
     * It also helps keep the JSON size down.
     */
    public function setIgnorePaths(array $ignorePaths): void
    {
        $this->ignorePaths = $ignorePaths;
    }

    /*
     * This method replaces one in Monolog\Formatter\JsonFormatter allowing us to use our own logic to set $data['trace']. We do this so:
     * a) We can control the volume of data being sent (LogDNA can only process 32000 bytes).
     * b) We can stop content being sent to LogDNA which causes their json parsing to break.
     */
    protected function normalizeException(Throwable $exception, int $depth = 0): array
    {
        $data = [
            'class'   => get_class($exception),
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
            'file'    => $exception->getFile() . ':' . $exception->getLine(),
        ];

        if ($this->includeStacktraces) {
            $data['trace'] = $this->exceptionTraceToArray($exception->getTrace());
        }

        if ($previous = $exception->getPrevious()) {
            $data['previous'] = $this->normalizeException($previous);
        }

        return $data;
    }

    private function exceptionTraceToArray(array $trace): array
    {
        $stack = [];

        foreach ($trace as $frame) {
            $file = ($frame['file'] ?? '');

            if (empty($file)) {
                continue;
            }

            foreach ($this->ignorePaths as $name) {
                if (mb_strpos($file, $name) !== false) {
                    continue 2;
                }
            }

            $stack[] = [
                'class'    => ($frame['class'] ?? ''),
                'function' => $frame['function'],
                'args'     => $this->frameArgsToArray($frame),
                'type'     => $this->callToString($frame),
                'file'     => $file,
                'line'     => ($frame['line'] ?? ''),
            ];
        }

        return $stack;
    }

    private function callToString(array $frame): string
    {
        if (! isset($frame['type'])) {
            return 'function';
        }

        if ($frame['type'] === '::') {
            return 'static';
        }

        if ($frame['type'] === '->') {
            return 'method';
        }
    }

    private function frameArgsToArray(array $frame): array
    {
        $params = [];

        if (! isset($frame['args'])) {
            return $params;
        }

        foreach ($frame['args'] as $arg) {
            $params[] = $this->argToString($arg);
        }

        return $params;
    }

    private function argToString($arg): string
    {
        if (is_array($arg)) {
            return 'array([' . implode(', ', array_map(fn ($arg) => $this->argToString($arg), $arg)) . ']), ' . count($arg);
        }

        if (is_object($arg)) {
            return get_class($arg); // @todo: Detail public arguments, but only for classes in specific namespaces.
        }

        if (is_string($arg)) {
            return 'string(' . $arg . ')';
        }

        if (is_int($arg)) {
            return 'int(' . $arg . ')';
        }

        if (is_float($arg)) {
            return 'float(' . $arg . ')';
        }

        if (is_bool($arg)) {
            return 'bool(' . ($arg ? 'true' : 'false') . ')';
        }

        return (string) $arg;
    }
}
