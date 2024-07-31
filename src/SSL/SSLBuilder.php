<?php

namespace CassandraNative\SSL;

class SSLBuilder 
{
    protected ?string $trustedCerts = null;

    protected bool $verify = true;

    protected ?string $clientCert = null;

    protected ?string $privateKey = null;

    protected ?string $passphrase = null;

    /**
     * Adds a certificate used to verify the nodes identity
     *
     * @param string $path
     * @return $this
     */
    public function withTrustedCerts(string $path): static
    {
        $this->trustedCerts = $path;
        return $this;
    }

    /**
     * Turns on or off certificate verification. Default is on
     *
     * @param bool $verify
     * @return $this
     */
    public function withVerifyFlags(bool $verify): static
    {
        $this->verify = $verify;
        return $this;
    }

    /**
     * Adds a certificate used to authenticate the client on the server side
     *
     * @param string $path
     * @return $this
     */
    public function withClientCert(string $path): static
    {
        $this->clientCert = $path;
        return $this;
    }

    /**
     * Adds a private key and optional passphrase used to authenticate the client on the server side.
     *
     * @param string $path
     * @param ?string $passphrase
     * @return $this
     */
    public function withPrivateKey(string $path, #[\SensitiveParameter] ?string $passphrase = null): static
    {
        $this->privateKey = $path;
        $this->passphrase = $passphrase;
        return $this;
    }

    /**
     * Builds the SSL options to be provided to a Cluster Builder instance
     *
     * @return SSLOptions
     */
    public function build(): SSLOptions
    {
        return new SSLOptions(
            $this->trustedCerts,
            $this->verify,
            $this->clientCert,
            $this->privateKey,
            $this->passphrase
        );
    }
}
