<?php

namespace CassandraNative\Compression;

class Lz4Compressor implements CompressorInterface
{

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'lz4';
    }

    /**
     * @inheritDoc
     */
    public function compress(string $data): string
    {
        // TODO: Implement compress() method.
    }

    /**
     * @inheritDoc
     */
    public function uncompress(string $data): string
    {
        // TODO: Implement uncompress() method.
    }
}