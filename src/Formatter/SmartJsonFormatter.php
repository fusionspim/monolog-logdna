<?php
namespace Fusions\Monolog\LogDna\Formatter;

use Throwable;

class SmartJsonFormatter extends BasicJsonFormatter
{
    protected $includeStacktraces  = true;
    protected $stackTraceModifiers = [];

    /**
     * Stack trace modifier functions allow frames to be modified or omitted from the final trace.
     *
     * This can be useful to exclude frame based on their file paths, for when you have deep stacks (e.g.  middleware)
     * that aren't relevant to your log output.
     *
     * It can also be useful to remove sensitive arguments from frames such as database credentials.
     */
    public function addStackTraceModifier(callable $fn): void
    {
        $this->stackTraceModifiers[] = $fn;
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
            $frame = [
                'class'    => ($frame['class'] ?? ''),
                'function' => $frame['function'],
                'args'     => $this->argsToArray($frame),
                'type'     => $this->callToString($frame),
                'file'     => ($frame['file'] ?? ''),
                'line'     => ($frame['line'] ?? ''),
            ];

            if (empty($frame['file'])) {
                continue;
            }

            foreach ($this->stackTraceModifiers as $stackTraceModifier) {
                $frame = call_user_func($stackTraceModifier, $frame);

                // If null, false, malformed or empty frame is returned then omit from the stack trace entirely.
                if (! is_array($frame) || empty($frame)) {
                    continue 2;
                }
            }

            $stack[] = $frame;
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

    private function argsToArray(array $frame): array
    {
        $params = [];

        if (! isset($frame['args'])) {
            return $params;
        }

        foreach ($frame['args'] as $arg) {
            if (is_array($arg)) {
                $params[] = 'array(' . count($arg) . ')';
            } elseif (is_object($arg)) {
                $params[] = get_class($arg);
            } elseif (is_string($arg)) {
                $params[] = 'string(' . $arg . ')';
            } elseif (is_int($arg)) {
                $params[] = 'int(' . $arg . ')';
            } elseif (is_float($arg)) {
                $params[] = 'float(' . $arg . ')';
            } elseif (is_bool($arg)) {
                $params[] = 'bool(' . ($arg ? 'true' : 'false') . ')';
            } else {
                $params[] = (string) $arg;
            }
        }

        return $params;
    }
}
