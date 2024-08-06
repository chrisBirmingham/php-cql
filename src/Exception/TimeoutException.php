<?php

namespace CassandraNative\Exception;

/**
 * Exception thrown when there is a timeout while reading or writing to cassandra
 */
class TimeoutException extends ConnectionException {}
