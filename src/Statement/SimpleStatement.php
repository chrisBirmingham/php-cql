<?php

namespace CassandraNative\Statement;

class SimpleStatement implements StatementInterface
{
    protected string $query;

    /**
     * @param string $query
     */
    public function __construct(string $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatement(): string
    {
        return $this->query;
    }
}
