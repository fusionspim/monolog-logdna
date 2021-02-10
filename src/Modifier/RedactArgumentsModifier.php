<?php
namespace Fusions\Monolog\LogDna\Modifier;

class RedactArgumentsModifier
{
    protected array $redactFrameArguments = [];

    /**
     * Redact arguments from stack trace frames belonging to a specific class method or function.
     * This is useful for excluding sensitive database credentials from log output.
     *
     * Accepts an array of arrays with the class, function and type keys to match the frame on:
     * [
     *     ['class' => 'PDO', 'function' => '__construct', 'type' => 'method'],
     *     [...],
     * ]
     */
    public function __construct(array $redactFrameArguments)
    {
        foreach ($redactFrameArguments as $redactFrameArgument) {
            if (isset($redactFrameArgument['class'], $redactFrameArgument['function'], $redactFrameArgument['type'])) {
                $this->redactFrameArguments[] = $redactFrameArgument;
            }
        }
    }

    public function __invoke(array $frame): ?array
    {
        foreach ($this->redactFrameArguments as $redactFrameArgument) {
            if (
                $redactFrameArgument['class'] === ($frame['class'] ?? '')
                && $redactFrameArgument['function'] === $frame['function']
                && $redactFrameArgument['type'] === $frame['type']
            ) {
                $frame['args'] = $this->redactFrameArguments($frame['args']);

                return $frame;
            }
        }

        return $frame;
    }

    protected function redactFrameArguments(array $args): array
    {
        $params = [];

        if (empty($args)) {
            return $params;
        }

        foreach ($args as $arg) {
            if (is_array($arg)) {
                $params[] = 'array(' . count($arg) . ')';
            } elseif (is_object($arg)) {
                $params[] = get_class($arg);
            } elseif (is_string($arg)) {
                $params[] = 'string(***REDACTED***)';
            } elseif (is_int($arg)) {
                $params[] = 'int(***REDACTED***)';
            } elseif (is_float($arg)) {
                $params[] = 'float(***REDACTED***)';
            } elseif (is_bool($arg)) {
                $params[] = 'bool(' . ($arg ? 'true' : 'false') . ')';
            } else {
                $params[] = '***REDACTED***';
            }
        }

        return $params;
    }
}
