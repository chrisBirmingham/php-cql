<?php

namespace CassandraNative\Exception;

/**
 * Exception thrown when there is a failure to compress or uncompress requests and responses to and from cassandra
 */
class CompressionException extends CassandraException {}