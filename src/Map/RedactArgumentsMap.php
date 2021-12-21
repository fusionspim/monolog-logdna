<?php
namespace Fusions\Monolog\LogDna\Map;

class RedactArgumentsMap
{
    private array $sensitiveArguments = [];

    /**
     * Redact arguments from stack trace frames belonging to a specific class method or function.
     * This is useful for excluding sensitive database credentials from log output.
     *
     * Accepts an array of sensitive data to redact from the stack trace frame arguments:
     * [
     *     'password1',
     *     'password2',
     * ]
     */
    public function __construct(array $sensitiveArguments)
    {
        foreach ($sensitiveArguments as $sensitiveArgument) {
            $this->sensitiveArguments[] = (string) $sensitiveArgument;
        }
    }

    public function __invoke(array $frame): ?array
    {
        $frame['args'] = $this->redactFrameArguments($frame['args']);

        return $frame;
    }

    protected function redactFrameArguments(array $args): array
    {
        return str_replace($this->sensitiveArguments, '***REDACTED***', $args);
    }
}
