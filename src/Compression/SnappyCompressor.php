<?php

namespace CassandraNative\Compression;

class SnappyCompressor implements CompressorInterface
{

    public function getName(): string
    {
        return 'snappy';
    }

    public function compress(string $data): string
    {
        return snappy_compress($data);
    }

    public function uncompress(string $data): string
    {
        return snappy_uncompress($data);
    }
}
