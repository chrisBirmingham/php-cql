<?php 

namespace CassandraNative\Cluster;

use CassandraNative\SSL\SSLOptions;

class ClusterOptions
{
    protected int $consistency;

    protected string $host;

    protected ?string $username;

    protected ?string $password;

    protected float connectTimeout;

    protected float requestTimeout;

    protected ?SSLOptions ssl; 

    protected int $port;

    protected bool $persistent;

    public function __construct(
        int $consistency,
        string $host,
        ?string $username,
        ?string $password,
        float connectTimeout,
        float requestTimeout,
        ?SSLOptions ssl, 
        int $port,
        bool $persistent 
    ) {
        $this->consistency = $consistency;
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->connectTimeout = $connectTimeout;
        $this->requestTimeout = $requestTimeout;
        $this->ssl = $ssl;
        $this->port = $port;
        $this->persistent = $persistent;
    }

    public function getDefaultConsistency(): int
    {
        return $this->consistency;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getConnectTimeout(): float
    {
        return $this->connectTimeout;
    }

    public function getRequestTimeout(): float
    {
        return $this->requestTimeout;
    }

    public function getSSL(): ?SSLOptions
    {
        return $this->ssl;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getPersistentSessions(): bool
    {
        return $this->persistent;
    }
}
