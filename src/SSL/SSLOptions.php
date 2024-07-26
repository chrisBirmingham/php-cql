<?php

namespace CassandraNative\SLL;

class SSLOptions
{
    protected string trustedCerts = '';

    protected bool verify = false;

    protected string clientCert = '';

    protected string privateKey = '';

    protected string|false passphrase = '';

    /**
     * @param string $trustedCerts,
     * @param bool $verify,
     * @param string $clientCert,
     * @param string $privateKey,
     * @param string|false $passphrase
     */
    public function __construct(
        string $trustedCerts,
        bool $verify,
        string $clientCert,
        string $privateKey,
        string|false $passphrase
    ) {
        $this->trustedCerts = $trustedCerts;
        $this->verify = $verify;
        $this->clientCert = $clientCert;
        $this->privateKey = $privateKey;
        $this->passphrase = $passphrase;
    }

    /**
     * @return string
     */
    public function getTrustedCerts(): string
    {
        return $this->trustedCerts;
    }

    /**
     * @return bool
     */
    public function getVerify(): bool
    {
        return $this->verify;
    }

    /**
     * @return string
     */
    public function getClientCerts(): string
    {
        return $this->clientCert;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @return string|false
     */
    public function getPassphrase(): string|false
    {
        return $this->passphrase;
    }
}
