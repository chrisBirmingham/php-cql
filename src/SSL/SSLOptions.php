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

    /**
     * Returns the SSL options in the format supported by stream_context_create
     *
     * @return array
     */
    public function get(): array
    {
        $options = [
            'verify_peer' => $this->verify
        ];

        if ($this->trustedCerts) {
            $options['cafile'] = $this->trustedCerts;
        }

        if ($this->clientCert) {
            $options['local_cert'] = $this->clientCert;
        }

        if ($this->privateKey) {
            $options['local_pk'] = $this->privateKey;

            if ($this->passphrase) {
                $options['passphrase'] = $this->passphrase;
            }
        }

        return $options;
    }
}
