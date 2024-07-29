<?php

namespace CassandraNative\SSL;

class SSLOptions
{
    protected ?string $trustedCerts;

    protected bool $verify;

    protected ?string $clientCert;

    protected ?string $privateKey;

    protected ?string $passphrase;

    /**
     * @param ?string $trustedCerts,
     * @param bool $verify,
     * @param ?string $clientCert,
     * @param ?string $privateKey,
     * @param ?string $passphrase
     */
    public function __construct(
        ?string $trustedCerts,
        bool $verify,
        ?string $clientCert,
        ?string $privateKey,
        ?string $passphrase
    ) {
        $this->trustedCerts = $trustedCerts;
        $this->verify = $verify;
        $this->clientCert = $clientCert;
        $this->privateKey = $privateKey;
        $this->passphrase = $passphrase;
    }

    public function get(): array
    {
        $options = [
            'verify_peer' => $this->verify
        ];

        if ($this->trustedCerts) {
            $options['capath'] = $this->trustedCerts;
        }

        if ($this->clientCert) {
            $options['cafile'] = $this->clientCert;
        }

        if ($this->privateKey) {
            $options['local_cert'] = $this->privateKey;
            if ($this->passphrase) {
                $options['passphrase'] = $this->passphrase; 
            }
        }

        return $options;
    }
}
