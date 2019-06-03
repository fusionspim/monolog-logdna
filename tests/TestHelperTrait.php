<?php
namespace Fusions\Test\Monolog\LogDna;

use DateTime;
use Monolog\Logger;

trait TestHelperTrait
{
    /**
     * @link https://github.com/Seldaek/monolog/blob/1.x/tests/Monolog/TestCase.php
     */
    public function getRecord(int $level = Logger::WARNING, string $message = 'test', array $context = []): array
    {
        return [
            'message'    => $message,
            'context'    => $context,
            'level'      => $level,
            'level_name' => Logger::getLevelName($level),
            'channel'    => 'test',
            'datetime'   => DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra'      => [],
        ];
    }
}
