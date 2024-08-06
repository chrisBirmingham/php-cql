# PHP CQL

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Native [Apache Cassandra](https://cassandra.apache.org) and [ScyllaDB](https://www.scylladb.com) connector for PHP based on the CQL binary protocol (v4),
without the need for an external extension.

Requires [PHP](https://www.php.net/) version >=8, Cassandra >1.2, and any ScyllaDB version.

Much of the API is built to emulate the [Datastax PHP Driver](https://docs.datastax.com/en/developer/php-driver/1.3/index.html). Original work by Uri Hartmann

## Installation

```bash
$ composer require intermaterium/php-cql
```

## Usage

### Simple Example

```php

<?php

require_once('vendor/autoload.php');

use CassandraNative\Cassandra;

// Connects to the node:
$clusterBuilder = new \CassandraNative\Cluster\ClusterBuilder();
$cassandra = $clusterBuilder->build();

// Queries a table:
$stmt = new \CassandraNative\Statement\SimpleStatement('SELECT col1, col2, col3 FROM my_table WHERE id=?')
$rows = $cassandra->execute(
    $stmt,
    [[1001, Cassandra::COLUMNTYPE_BIGINT]]
    Cassandra::CONSISTENCY_ONE
);

// $rows, for example, may contain:
// Array
// (
//     [0] => Array
//         (
//             [col1] => first row
//             [col2] => 1
//             [col3] => 0x111111
//         )
//
//     [1] => Array
//         (
//             [col1] => second row
//             [col2] => 2
//             [col3] => 0x222222
//         )
//
// )

// Prepares a statement:
$stmt = $cassandra->prepare('UPDATE my_table SET col2=?,col3=? WHERE col1=?');

// Executes a prepared statement:
$values = ['col2' => 5, 'col3' => '0x55', 'col1' => 'five'];
$pResult = $cassandra->execute($stmt, $values);

// Upon success, $pResult would be:
// Array
// (
//     [0] => Array
//         (
//             [result] => success
//         )
//
// )

// Closes the connection:
$cassandra->close();
```

### Advanced Example

```php
require_once (__DIR__ . '/vendor/autoload.php');

$sslBuilder = new \CassandraNative\SSL\SSLBuilder();
$sslBuilder
    ->withClientCert(__DIR__ . '/certs/localhost.cer')
    ->withPrivateKey(__DIR__ . '/certs/localhost.key.pem');
    ->withTrustedCerts(__DIR__ . '/certs/localhost.cer.pem');

$clusterBuilder = new \CassandraNative\Cluster\ClusterBuilder();
$clusterBuilder
    ->withContactPoints(['host1', 'host2', 'host3'])
    ->withDefaultConsistency(CassandraNative\Cassandra::CONSISTENCY_EACH_QUORUM)
    ->withSSL($sslBuilder->build());

$cassandra = $clusterBuilder->build();

$stmt = new \CassandraNative\Statement\SimpleStatement('DESCRIBE TABLES');
$rows = $cassandra->execute($stmt);

echo "There are " . $rows->count() . " tables\n";

foreach ($rows as $row) {
    echo $row['keyspace_name'] . ":" . $row['name'] . "\n";
}
```

## External links

1. Datastax's blog introducing the binary protocol:
http://www.datastax.com/dev/blog/binary-protocol

2. CQL definitions
https://cassandra.apache.org/_/native_protocol.html


## License

    The MIT License (MIT)

    Copyright (c) 2023 Uri Hartmann
    Copyright (c) 2024 Christopher Birmingham

    Permission is hereby granted, free of charge, to any person obtaining a
    copy of this software and associated documentation files (the "Software"),
    to deal in the Software without restriction, including without limitation
    the rights to use, copy, modify, merge, publish, distribute, sublicense,
    and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
    DEALINGS IN THE SOFTWARE.
