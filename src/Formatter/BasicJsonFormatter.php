<?php
namespace Fusions\Monolog\LogDna\Formatter;

use Monolog\Formatter\JsonFormatter;

class BasicJsonFormatter extends JsonFormatter
{
    public function format(array $record): string
    {
        return parent::format([
            'lines' => [
                [
                    'timestamp' => $record['datetime']->getTimestamp(),
                    'line'      => $record['message'],
                    'app'       => $record['channel'],
                    'level'     => $record['level_name'],
                    'meta'      => $record['context'],
                ],
            ],
        ]);
    }
}
