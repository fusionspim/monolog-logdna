<?php
namespace Fusions\Monolog\LogDna\Handler;

use GuzzleHttp\{Client as HttpClient, ClientInterface as HttpClientInterface};
use Fusions\Monolog\LogDna\Formatter\JsonFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

/**
 * @see: https://docs.logdna.com/reference#logsingest
 */
class LogDnaHandler extends AbstractProcessingHandler
{
    public const LOGDNA_INGESTION_URL = 'https://logs.logdna.com/logs/ingest';

    private string $ipAddress                    = '';
    private string $macAddress                   = '';
    private array $tags                          = [];
    private HttpClientInterface|null $httpClient = null;
    private ResponseInterface|null $lastResponse = null;
    private string|null $lastBody                = null;

    public function __construct(private string $ingestionKey, private string $hostName, string $level = Logger::DEBUG, bool $bubble = true)
    {
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

    protected function getHttpClient(): HttpClientInterface
    {
        if (! $this->httpClient) {
            $this->setHttpClient(new HttpClient(['timeout' => 5]));
        }

        return $this->httpClient;
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new JsonFormatter;
    }

    public function write(array $record): void
    {
        $this->lastBody = $record['formatted'];

        $this->lastResponse = $this->getHttpClient()->request('POST', static::LOGDNA_INGESTION_URL, [
            'headers' => [
                'Content-Type' => 'application/json; charset=UTF-8',
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
            'body' => $record['formatted'],
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
