<?php

namespace CassandraNative\Exception;

/**
 * Exception thrown when the client is unable to establish a connection to any of the provided hosts
 */
class NoHostsAvailableException extends CassandraException {}
