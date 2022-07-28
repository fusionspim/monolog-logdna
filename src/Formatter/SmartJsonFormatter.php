<?php
namespace Fusions\Monolog\LogDna\Formatter;

use Throwable;

class SmartJsonFormatter extends BasicJsonFormatter
{
    protected array $maps         = [];
    protected array $filters      = [];

    public function __construct(
        int $batchMode = self::BATCH_MODE_JSON,
        bool $appendNewline = true,
        bool $ignoreEmptyContextAndExtra = false,
        bool $includeStacktraces = true // We're overriding the default here.
    ) {
        parent::__construct($batchMode, $appendNewline, $ignoreEmptyContextAndExtra, $includeStacktraces);
    }

    public function addMap(callable $fn): void
    {
        $this->maps[] = $fn;
    }

    public function addFilter(callable $fn): void
    {
        $this->filters[] = $fn;
    }

    /*
     * This method replaces one in Monolog\Formatter\JsonFormatter allowing us to use our own logic to set $data['trace']. We do this so:
     * a) We can control the volume of data being sent (LogDNA can only process 32000 bytes).
     * b) We can stop content being sent to LogDNA which causes their json parsing to break.
     */
    protected function normalizeException(Throwable $exception, int $depth = 0): array
    {
        $data = [
            'class'   => $exception::class,
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

            $stack[] = $frame;
        }

        foreach ($this->maps as $map) {
            $stack = array_map($map, $stack);
        }

        foreach ($this->filters as $filter) {
            $stack = array_filter($stack, $filter);
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
                $params[] = $arg::class;
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
