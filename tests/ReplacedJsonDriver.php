<?php
namespace Fusions\Test\Monolog\LogDna;

use Spatie\Snapshots\Drivers\JsonDriver;

/**
 * Extends the default JSON driver with the ability to replace key/value pairs in the JSON.
 * This is helpful for fields that may change, such as timestamps and file paths.
 */
class ReplacedJsonDriver extends JsonDriver
{
    public function __construct(protected array $replacements = [])
    {
        foreach ($this->replacements as $field => $replacement) {
            $this->replacements[$field] = (! is_callable($replacement) ? fn () => $replacement : $replacement);
        }
    }

    public function serialize($data): string
    {
        return parent::serialize($this->replaceData($data));
    }

    public function match($expected, $actual): void
    {
        parent::match($expected, $this->replaceData($actual));
    }

    protected function replaceData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->replaceData($value);
            } else {
                $data[$key] = isset($this->replacements[$key])
                    ? $this->replacements[$key]($value)
                    : $value;
            }
        }

        return $data;
    }
}
