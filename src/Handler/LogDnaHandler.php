<?php
namespace Fusions\Monolog\LogDna\Handler;

use Fusions\Monolog\LogDna\Formatter\BasicJsonFormatter;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

/**
 * @link: https://docs.logdna.com/reference#logsingest
 */
class LogDnaHandler extends AbstractProcessingHandler
{
    public const LOGDNA_INGESTION_URL = 'https://logs.logdna.com/logs/ingest';
    public const LOGDNA_BYTE_LIMIT    = 30000;

    private $ingestionKey = '';
    private $hostName     = '';
    private $ipAddress    = '';
    private $macAddress   = '';
    private $tags         = [];
    private $httpClient;
    private $lastResponse;
    private $lastBody;

    public function __construct(string $ingestionKey, string $hostName, $level = Logger::DEBUG, $bubble = true)
    {
        $this->ingestionKey = $ingestionKey;
        $this->hostName     = $hostName;

        parent::__construct($level, $bubble);
    }

    public function setIpAddress(string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function setMacAddress(string $macAddress): void
    {
        $this->macAddress = $macAddress;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    private function getHttpClient(): HttpClientInterface
    {
        if (! $this->httpClient) {
            $this->setHttpClient(new HttpClient(['timeout' => 5]));
        }

        return $this->httpClient;
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new BasicJsonFormatter;
    }

    public function write(array $record): void
    {
        $body = $record['formatted'];

        if (mb_strlen($body, '8bit') > static::LOGDNA_BYTE_LIMIT) {
            $decodedBody = json_decode($body, true);

            $body = json_encode([
                'lines' => [
                    [
                        'timestamp' => $decodedBody['lines'][0]['timestamp'] ?? '',
                        'line'      => $decodedBody['lines'][0]['line'] ?? '',
                        'app'       => $decodedBody['lines'][0]['app'] ?? '',
                        'level'     => $decodedBody['lines'][0]['level'] ?? '',
                        'meta'      => [
                            'longException' => mb_substr($body, 0, static::LOGDNA_BYTE_LIMIT, '8bit'),
                        ],
                    ],
                ],
            ]);
        }

        $this->lastBody = $body;

        $this->lastResponse = $this->getHttpClient()->request('POST', static::LOGDNA_INGESTION_URL, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'auth' => [
                $this->ingestionKey, '',
            ],
            'query' => [
                'hostname' => $this->hostName,
                'mac'      => $this->macAddress,
                'ip'       => $this->ipAddress,
                'now'      => $record['datetime']->getTimestamp(),
                'tags'     => $this->tags,
            ],
            'body' => $body,
        ]);
    }

    public function getLastResponse(): ResponseInterface
    {
        return $this->lastResponse;
    }

    public function getLastBody(): string
    {
        return $this->lastBody;
    }
}
