<?php

namespace CassandraNative\SSL;

class SSLOptions
{
    protected string|false $trustedCerts;

    protected bool $verify;

    protected string|false $clientCert;

    protected string|false $privateKey;

    protected string|false $passphrase;

    /**
     * @param string|false $trustedCerts,
     * @param bool $verify,
     * @param string|false $clientCert,
     * @param string|false $privateKey,
     * @param string|false $passphrase
     */
    public function __construct(
        string|false $trustedCerts,
        bool $verify,
        string|false $clientCert,
        string|false $privateKey,
        string|false $passphrase
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
