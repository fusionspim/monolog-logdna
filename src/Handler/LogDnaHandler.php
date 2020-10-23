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
    public const LOGDNA_INGESTION_URL   = 'https://logs.logdna.com/logs/ingest';
    public const LOGDNA_META_DATA_LIMIT = 30_000;

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

    protected function getHttpClient(): HttpClientInterface
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

        /**
         * We need to pretty print the metadata JSON before we check its the size, as LogDNA's 32KB limit applies to the
         * number bytes of metadata **AFTER** LogDNA's API (Restify) has parsed and pretty formatted the JSON it.
         *
         * It **DOES NOT* apply to the number of bytes actually sent in the API request, like you'd expect.
         *
         * Essentially this is guess work on our part - we're hoping that the size of the JSON pretty printed is roughly
         * the same as the parsing process on LogDNA's end. It's the best we can do under the circumstances.
         *
         * Confirmed the behaviour in ticket 10474.
         */
        $decodedBody = json_decode($body, true);

        if (mb_strlen(json_encode($decodedBody['lines'][0]['meta'], JSON_PRETTY_PRINT), '8bit') > static::LOGDNA_META_DATA_LIMIT) {
            $body = json_encode([
                'lines' => [
                    [
                        'timestamp' => $decodedBody['lines'][0]['timestamp'] ?? '',
                        'line'      => $decodedBody['lines'][0]['line'] ?? '',
                        'app'       => $decodedBody['lines'][0]['app'] ?? '',
                        'level'     => $decodedBody['lines'][0]['level'] ?? '',
                        'meta'      => [
                            'truncated' => mb_substr(
                                json_encode($decodedBody['lines'][0]['meta'], JSON_PRETTY_PRINT), 0, static::LOGDNA_META_DATA_LIMIT,
                                '8bit'
                            ),
                        ],
                    ],
                ],
            ]);
        }

        $this->lastBody = $body;

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
