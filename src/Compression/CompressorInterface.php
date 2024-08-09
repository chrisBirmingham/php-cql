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
     * @return string
     */
    public function compress(string $data): string;

    /**
     * @param string $data
     * @return string
     */
    public function uncompress(string $data): string;
}
