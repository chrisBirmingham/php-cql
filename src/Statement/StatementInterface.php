<?php

namespace CassandraNative\Statement;

interface StatementInterface
{
    /**
     * @return string|array
     */
    public function getStatement(): string|array;
}
