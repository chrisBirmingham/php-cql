<?php

namespace CassandraNative\Cluster;

use CassandraNative\Cassandra;
use CassandraNative\SSL\SSLOptions;

class ClusterBuilder
{
    protected int $consistency = Cassandra::CONSISTENCY_ONE;

    protected string $host = 'localhost';

    protected ?string $username = null;

    protected ?string $password = null;

    protected float $connectTimeout = 30;

    protected float $requestTimeout = 30;

    protected ?SSLOptions $ssl = null; 

    protected int $port = 9042;

    protected bool $persistent = false;

    /**
     * @param int $consistency
     * @return $this
     */
    public function withDefaultConsistency(int $consistency): static
    {
        if ($consistency < Cassandra::CONSISTENCY_ANY || $consistency > CONSISTENCY_LOCAL_ONE) {
            throw new \InvalidArgumentException();
        }

        $this->consistency = $consistency;
        return $this;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function withContactPoint(string $host): static
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function withPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withCredentials(string $username, string $password): static
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    /**
     * @param float $timeout
     * @return $this
     */
    public function withConnectTimeout(float $timeout): static
    {
        $this->connectTimeout = $timeout;
        return $this;
    }

    /**
     * @param float $timeout
     * @return $this
     */
    public function withRequestTimeout(float $timeout): static
    {
        $this->requestTimeout = $timeout;
        return $this;
    }

    /**
     * @param ?SSLOptions $ssl
     * @return $this
     */
    public function withSSL(?SSLOptions $ssl): static 
    {
        $this->ssl = $ssl;
        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function withPersistentSessions(bool $enabled): static
    {
        $this->persistent = $enabled;
        return $this;
    }

    /**
     * @return Cassandra
     */
    public function build(): Cassandra
    {
        $options = new ClusterOptions(
            $this->consistency,
            $this->host,
            $this->username,
            $this->password,
            $this->connectTimeout,
            $this->requestTimeout,
            $this->ssl,
            $this->port,
            $this->persistent
        );

        return new Cassandra($options);
    }
}
