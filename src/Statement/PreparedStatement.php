<?php

namespace CassandraNative\Statement;

class PreparedStatement
{
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var array
	 */
	private $columns;

	/**
	 * @var array
	 */
	private $bindValues = [];

	/**
	 * @param array $statement
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
		return $this->columns
	}

	/**
	 * @param string $param
	 * @return mixed
	 * @throws \Exception
	 */
	public function getBindValue(string $param): mixed
	{
		if (!isset($this->bindValues[$param]) {
			throw new \Exception('No value provided for bound parameter ' . $param)
		}

		return $this->bindValues[$param];
	}

	/**
	 * @param string $param
	 * @param mixed $value
	 */
	public function bindValue(string $param, mixed $value): void
	{
		$this->bindValues[$param] = $value; 
	}
}
