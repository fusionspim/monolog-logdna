<?php
namespace Fusions\Monolog\LogDna\Modifier;

class RedactArgumentsModifier
{
    protected array $redactFrameArguments = [];

    public function __construct(array $redactFrameArguments)
    {
        $this->redactFrameArguments = $redactFrameArguments;
    }

    public function __invoke(array $frame): ?array
    {
        foreach ($this->redactFrameArguments as $redactFrameArgument) {
            if (
                $redactFrameArgument['class'] === ($frame['class'] ?? '') &&
                $redactFrameArgument['function'] === $frame['function'] &&
                $redactFrameArgument['type'] === $frame['type']
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
