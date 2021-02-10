<?php
namespace Fusions\Monolog\LogDna\Modifier;

class IgnorePathsModifier
{
    protected array $ignorePaths = [];

    public function __construct(array $ignorePaths)
    {
        $this->ignorePaths = $ignorePaths;
    }

    public function __invoke(array $frame): ?array
    {
        foreach ($this->ignorePaths as $path) {
            if (mb_strpos($frame['file'], $path) !== false) {
                return null;
            }
        }

        return $frame;
    }
}
