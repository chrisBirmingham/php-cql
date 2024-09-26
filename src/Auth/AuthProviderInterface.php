<?php

namespace CassandraNative\Auth;

interface AuthProviderInterface
{
    /**
     * Returns the fully qualified name of the Java class this authentication provider answers for. The class name
     * Cassandra is configured for can be found in the cassandra.yaml file under the authenticator.class_name directive
     *
     * @return string
     */
    public function mechanism(): string;

    /**
     * The initial response to an authentication response from a Cassandra node
     *
     * @return string
     */
    public function initialResponse(): string;

    /**
     * Method called when cassandra issues an authentication challenge after an initial authentication response. Some
     * authentication providers, such as plaintext auth providers, don't require an auth challenge
     *
     * @param string $challenge A string token describing the challenge issued by the cassandra node
     * @return string|false     A string if the provider can respond to the challenge or false if the provider doesn't
     *                          support auth challenges
     */
    public function challengeResponse(string $challenge): string|false;
}
