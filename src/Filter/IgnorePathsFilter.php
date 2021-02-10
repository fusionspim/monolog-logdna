<?php
namespace Fusions\Monolog\LogDna\Filter;

class IgnorePathsFilter
{
    protected array $ignorePaths = [];

    /**
     * Omit stack trace frames containing specific file paths.
     * This is useful when you have deep stacks (e.g. middleware) or want to exclude vendor components that aren't relevant.
     * It also helps keep the JSON size down.
     *
     * Accepts an array of string file paths:
     * [
     *     '/path/to/file.php',
     *     '...',
     * ]
     */
    public function __construct(array $ignorePaths)
    {
        $this->ignorePaths = $ignorePaths;
    }

    public function __invoke(array $frame): bool
    {
        foreach ($this->ignorePaths as $path) {
            if (mb_strpos($frame['file'], $path) !== false) {
                return false;
            }
        }

        return true;
    }
}
