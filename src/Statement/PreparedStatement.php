<?php

namespace CassandraNative\Statement;

class PreparedStatement implements StatementInterface
{
    protected string $id;

    protected array $columns;

    /**
     * @param string $id
     * @param array $columns
     */
    public function __construct(string $id, array $columns)
    {
        $this->id = $id;
        $this->columns = $columns;
    }

    /**
     * @return string
     */
    public function getId(): string 
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatement(): array
    {
        return [
            'id' => $this->id,
            'columns' => $this->columns
        ];
    }
}
