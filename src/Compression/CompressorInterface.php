<?php

namespace CassandraNative\Compression;

interface CompressorInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $data
     * @return string|false
     */
    public function compress(string $data): string|false;

    /**
     * @param string $data
     * @return string|false
     */
    public function uncompress(string $data): string|false;
}
