<?php

namespace CassandraNative\SLL;

class SSLBuilder 
{
    protected string trustedCerts = '';

    protected bool verify = false;

    protected string clientCert = '';

    protected string privateKey = '';

    protected string|false passphrase = '';

    /**
     * @param string $path
     * @return $this
     */
    public function withTrustedCerts(string $path): static
    {
        $this->trustedCerts = $path;
        return $this;
    }

    /**
     * @param bool $verify
     * @return $this
     */
    public function withVerifyFlags(bool $verify): static
    {
        $this->verify = $verify;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function withClientCert(string $path): static
    {
        $this->clientCert = $path;
        return $this;
    }

    /**
     * @param string $path
     * @param string $passphrase
     * @return $this
     */
    public function withPrivateKey(string $path, string|false $passphrase = false): static
    {
        $this->privateKey = $path;
        $this->passphrase = $passphrase;
        return $this;
    }

    /**
     * @return SSLOptions
     */
    public function build(): SSLOptions
    {
        return new SSLOptions(
            $this->trustedCerts,
            $this->verify,
            $this->clientCert,
            $this->passphrase
        );
    }
}
