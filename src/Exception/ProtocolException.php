<?php

namespace CassandraNative\Exception;

/**
 * Exception thrown when there is an invalid response from cassandra
 */
class ProtocolException extends CassandraException
{
    protected int $opcode;

    /**
     * @param string $message
     * @param int $opcode
     */
    public function __construct(string $message, int $opcode = 0)
    {
        $this->opcode = $opcode;
        parent::__construct($message);
    }

    /**
     * @return int
     */
    public function getOpcode(): int
    {
        return $this->opcode;
    }
}
