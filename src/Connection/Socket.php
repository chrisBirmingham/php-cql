<?php

namespace CassandraNative\Connection;

use CassandraNative\Cluster\ClusterOptions;
use CassandraNative\Exception\ConnectionException;
use CassandraNative\Exception\TimeoutException;
use CassandraNative\SSL\SSLOptions;

class Socket
{
    protected bool $persistent;

    /**
     * @var resource|false
     */
    protected $stream = false;

    /**
     * Connect to a Cassandra host
     *
     * @param ClusterOptions $clusterOptions
     * @throws ConnectionException
     */
    public function connect(ClusterOptions $clusterOptions): void
    {
        $this->persistent = $clusterOptions->getPersistentSessions();
        $hosts = $clusterOptions->getHosts();
        $hostId = array_rand($hosts); // Connection Pooling what's that?
        $host = $hosts[$hostId];
        $port = $clusterOptions->getPort();
        $connectTimeout = $clusterOptions->getConnectTimeout();
        $address = 'tcp://' . $host . ':' . $port;

        $connectionFlags = STREAM_CLIENT_CONNECT;

        if ($this->persistent) {
            $connectionFlags |= STREAM_CLIENT_PERSISTENT;
        }

        $options = [];
        $sslOptions = $clusterOptions->getSSL();

        if ($sslOptions instanceof SSLOptions) {
            $options['ssl'] = $sslOptions->get();
        }

        $stream = @stream_socket_client(
            $address,
            $errno,
            $errstr,
            $connectTimeout,
            $connectionFlags,
            stream_context_create($options)
        );

        if ($stream === false) {
            throw new ConnectionException('Socket connect to ' . $host . ':' . $port . ' failed: ' . '(' . $errno . ') ' . $errstr);
        }

        if ($sslOptions instanceof SSLOptions) {
            // Persistent connections retain SSL. Check that we already have an SSL enabled connection before trying to
            // enable one
            $meta = stream_get_meta_data($stream);
            if (!isset($meta['crypto'])) {
                if (!stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($stream);
                    throw new ConnectionException('Failed to establish encrypted connection to ' . $host . ':' . $port);
                }
            }
        }

        $requestTimeout = $clusterOptions->getRequestTimeout();

        if ($requestTimeout > 0) {
            $timeoutSeconds = floor($requestTimeout);
            $timeoutMicroseconds = ($requestTimeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($stream, $timeoutSeconds, $timeoutMicroseconds);
        }

        $this->stream = $stream;
    }

    /**
     * Checks if a socket is persistent and has already been read from
     *
     * @return bool
     */
    public function isPersistent(): bool
    {
         return $this->persistent && ftell($this->stream) != 0;
    }

    /**
     * Reads data with a specific size from socket.
     *
     * @param int $size Requested data size.
     *
     * @return string Incoming data.
     *
     * @throws ConnectionException
     */
    public function read(int $size): string
    {
        $data = '';
        do {
            $readSize = $size - strlen($data);
            $buff = @fread($this->stream, $readSize);
            if ($buff === false) {
                if (stream_get_meta_data($this->stream)['timed_out']) {
                    throw new TimeoutException('Timeout occurred while reading from socket');
                }

                throw new ConnectionException('Failed to read packet from socket');
            }
            $data .= $buff;
        } while (strlen($data) < $size);

        return $data;
    }

    /**
     * Writes data to the socket
     *
     * @param string $body The body to write
     * @throws ConnectionException
     * @throws TimeoutException
     */
    public function write(string $body): void
    {
        if (fwrite($this->stream, $body) === false) {
            if (stream_get_meta_data($this->stream)['timed_out']) {
                throw new TimeoutException('Timeout occurred while writing to socket');
            }

            throw new ConnectionException('Failed to write packet to socket');
        }
    }

    /**
     * Closes an opened connection.
     */
    public function close(): void
    {
        if (!$this->stream) {
            return;
        }

        fclose($this->stream);
        $this->stream = false;
    }

    function __destruct()
    {
        if ($this->persistent) {
            return;
        }

        $this->close();
    }
}