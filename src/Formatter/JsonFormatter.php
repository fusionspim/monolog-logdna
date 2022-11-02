<?php

namespace Fusions\Monolog\LogDna\Formatter;

use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;

class JsonFormatter extends MonologJsonFormatter
{
    public function __construct(
        int $batchMode = MonologJsonFormatter::BATCH_MODE_JSON,
        bool $appendNewline = true,
        bool $ignoreEmptyContextAndExtra = false,
        bool $includeStacktraces = true // By default we want stack traces.
    ) {
        parent::__construct($batchMode, $appendNewline, $ignoreEmptyContextAndExtra, $includeStacktraces);
    }

    public function format(array $record): string
    {
        return parent::format([
            'lines' => [
                [
                    'timestamp' => $record['datetime']->getTimestamp(),
                    'line' => $record['message'],
                    'app' => $record['channel'],
                    'level' => $record['level_name'],
                    'meta' => $record['context'],
                ],
            ],
        ]);
    }
}
