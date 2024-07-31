<?php

namespace CassandraNative\Result;

class Rows implements \ArrayAccess, \Iterator
{
    protected array $results;

    /**
     * @param array[] $results
     */
    public function __construct(array $results)
    {
        $this->results = $results;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->results);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        reset($this->results);
    }

    /**
     * {@inheritDoc}
     */
    public function current(): array
    {
        return current($this->results);
    }

    /**
     * {@inheritDoc}
     */
    public function key(): int
    {
        return key($this->results);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        next($this->results);
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        $last = array_key_last($this->results);
        return key($this->results) < $last;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return key_exists($offset, $this->results);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet(mixed $offset): ?array
    {
        return $this->results[$offset] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->results[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->results[$offset]);
    }
}
