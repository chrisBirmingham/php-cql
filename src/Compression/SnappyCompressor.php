<?php

namespace CassandraNative\Compression;

use CassandraNative\Exception\CompressionException;

class SnappyCompressor implements CompressorInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'snappy';
    }

    /**
     * @inheritDoc
     */
    public function compress(string $data): string|false
    {
        return snappy_compress($data);
    }

    /**
     * @inheritDoc
     */
    public function uncompress(string $data): string|false
    {
        return snappy_uncompress($data);
    }
}
