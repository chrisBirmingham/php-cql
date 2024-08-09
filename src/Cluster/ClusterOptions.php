<?php 

namespace CassandraNative\Cluster;

use CassandraNative\Compression\CompressorInterface;
use CassandraNative\SSL\SSLOptions;

class ClusterOptions
{
    protected int $consistency;

    /**
     * @var string[]
     */
    protected array $hosts;

    protected ?string $username;

    protected ?string $password;

    protected float $connectTimeout;

    protected float $requestTimeout;

    protected ?SSLOptions $ssl; 

    protected int $port;

    protected bool $persistent;

    protected ?CompressorInterface $compressor;

    /**
     * @param int $consistency
     * @param string[] $hosts
     * @param ?string $username
     * @param ?string $password
     * @param float $connectTimeout
     * @param float $requestTimeout
     * @param ?SSLOptions $ssl
     * @param int $port
     * @param bool $persistent
     * @param ?CompressorInterface $compressor
     */
    public function __construct(
        int $consistency,
        array $hosts,
        ?string $username,
        #[\SensitiveParameter] ?string $password,
        float $connectTimeout,
        float $requestTimeout,
        ?SSLOptions $ssl, 
        int $port,
        bool $persistent,
        ?CompressorInterface $compressor
    ) {
        $this->consistency = $consistency;
        $this->hosts = $hosts;
        $this->username = $username;
        $this->password = $password;
        $this->connectTimeout = $connectTimeout;
        $this->requestTimeout = $requestTimeout;
        $this->ssl = $ssl;
        $this->port = $port;
        $this->persistent = $persistent;
        $this->compressor = $compressor;
    }

    /**
     * @return int
     */
    public function getDefaultConsistency(): int
    {
        return $this->consistency;
    }

    /**
     * @return string[]
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    /**
     * @return ?string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return ?string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return float
     */
    public function getConnectTimeout(): float
    {
        return $this->connectTimeout;
    }

    /**
     * @return float
     */
    public function getRequestTimeout(): float
    {
        return $this->requestTimeout;
    }

    /**
     * @return ?SSLOptions
     */
    public function getSSL(): ?SSLOptions
    {
        return $this->ssl;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return bool
     */
    public function getPersistentSessions(): bool
    {
        return $this->persistent;
    }

    /**
     * @return ?CompressorInterface
     */
    public function getCompressor(): ?CompressorInterface
    {
        return $this->compressor;
    }
}
