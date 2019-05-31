<?php
namespace Fusions\Monolog\LogDna\Formatter;

class SmartJsonFormatter extends BasicJsonFormatter
{
    private $ignorePaths = [];

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
    protected function normalizeException($exception)
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
                'args'     => $this->argsToArray($frame),
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

        if ($frame['type'] == '::') {
            return 'static';
        }

        if ($frame['type'] == '->') {
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
