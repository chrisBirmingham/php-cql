<?php

namespace CassandraNative\Cluster;

use CassandraNative\Cassandra;
use CassandraNative\SSL\SSLOptions;

class ClusterBuilder
{
    protected int $consistency = Cassandra::CONSISTENCY_ONE;

    /**
     * @var string[]
     */
    protected array $hosts = ['localhost'];

    protected ?string $username = null;

    protected ?string $password = null;

    protected float $connectTimeout = 30;

    protected float $requestTimeout = 30;

    protected ?SSLOptions $ssl = null; 

    protected int $port = 9042;

    protected bool $persistent = false;

    /**
     * Sets the default consistency for all queries to the cluster. Default is CONSISTENCY_ONE
     *
     * @param int $consistency
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function withDefaultConsistency(int $consistency): static
    {
        if ($consistency < Cassandra::CONSISTENCY_ANY || $consistency > Cassandra::CONSISTENCY_LOCAL_ONE) {
            throw new \InvalidArgumentException('Invalid consistency provided. Must be between CONSISTENCY_ANY and CONSISTENCY_LOCAL_ONE');
        }

        $this->consistency = $consistency;
        return $this;
    }

    /**
     * Sets a list of initial hosts to connect too. The client will pick one at random to attempt a connection
     * Default is localhost
     *
     * @param string[] $hosts
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function withContactPoints(array $hosts): static
    {
        if (count($hosts) < 1) {
            throw new \InvalidArgumentException('Contact hosts cannot be empty');
        }

        $this->hosts = $hosts;
        return $this;
    }

    /**
     * Sets the port to connect too. Default is 9042
     *
     * @param int $port
     * @return $this
     */
    public function withPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Sets the plaintext credentials for authenticating the connection to the cluster
     * Default no authentication
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withCredentials(string $username, #[\SensitiveParameter] string $password): static
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    /**
     * Sets timeout for connecting to the Cluster
     *
     * @param float $timeout
     * @return $this
     */
    public function withConnectTimeout(float $timeout): static
    {
        $this->connectTimeout = $timeout;
        return $this;
    }

    /**
     * Sets the timeout for requests to the cluster
     *
     * @param float $timeout
     * @return $this
     */
    public function withRequestTimeout(float $timeout): static
    {
        $this->requestTimeout = $timeout;
        return $this;
    }

    /**
     *  Sets SSL connection settings built via the SSLBuilder object
     * Default is unencrypted
     *
     * @param SSLOptions $ssl
     * @return $this
     */
    public function withSSL(SSLOptions $ssl): static
    {
        $this->ssl = $ssl;
        return $this;
    }

    /**
     * Sets whether the connection to the cluster should be persistent or not
     *
     * @param bool $enabled Default is false
     * @return $this
     */
    public function withPersistentSessions(bool $enabled): static
    {
        $this->persistent = $enabled;
        return $this;
    }

    /**
     * Builds a cluster based on current settings
     *
     * @return Cassandra
     * @throws \Exception
     */
    public function build(): Cassandra
    {
        $options = new ClusterOptions(
            $this->consistency,
            $this->hosts,
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
