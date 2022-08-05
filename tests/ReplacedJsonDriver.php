<?php
namespace Fusions\Test\Monolog\LogDna;

use Spatie\Snapshots\Drivers\JsonDriver;

/**
 * Extends the default JSON driver with the ability to replace key/value pairs in the JSON.
 * This is helpful for fields that may change, such as timestamps.
 */
class ReplacedJsonDriver extends JsonDriver
{
    public function __construct(protected array $replacements = [])
    {
    }

    public function serialize($data): string
    {
        return parent::serialize($this->replace($data));
    }

    public function match($expected, $actual): void
    {
        parent::match($expected, $this->replace($actual));
    }

    protected function replace(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->replace($value);
            } else {
                $data[$key] = $this->replacements[$key] ?? $value;
            }
        }

        return $data;
    }
}
