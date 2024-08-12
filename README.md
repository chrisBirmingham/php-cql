# PHP CQL

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Native [Apache Cassandra](https://cassandra.apache.org) and 
[ScyllaDB](https://www.scylladb.com) connector for PHP applications
using the CQL binary protocol (v4), without the need for an external
extension.

Requires [PHP](https://www.php.net/) version >=8, Cassandra >1.2, 
and any ScyllaDB version.

Much of the API is built to emulate the [Datastax PHP Driver](https://docs.datastax.com/en/developer/php-driver/1.3/index.html). 
Original work by Uri Hartmann

## Installation

```bash
$ composer require intermaterium/cassandra-native
```

## Features

* Simple and Prepared Statements
* SSL Encryption
* Persistent Connections
* Compression via LZ4 and Snappy.

## Missing Features

* Batch Statements
* Async queries
* Result Paging

## Usage

### Cluster

A Cassandra cluster can be built via the `ClusterBuilder` class.

```php
$clusterBuilder = new \CassandraNative\Cluster\ClusterBuilder();
$cassandra = $clusterBuilder->build();
```

By default the Cluster will try to connect to localhost.
You can specify a set of IP/hostnames to connect to using the 
`withContactPoints` method. Unlike the Datastax Driver, when 
connecting the client will pick a contact host at random and attempt
to connect to it.

```php
$clusterBuilder = new \CassandraNative\Cluster\ClusterBuilder();
$clusterBuilder->withContactPoints(['1.0.0.0', '2.0.0.0']);
$cassandra = $clusterBuilder->build();
```

When connecting, the created Cassandra instance doesn't connect to 
a specific keyspace. Calling `connect` on the created Cassandra 
instance with a keyspace name will execute a `USE $keyspace` query.

```php
$cassandra->connect('system');
```

### SSL 

You can turn on SSL Encryption via the `SSLBuilder` class and 
pass the result of a call to the `build` method to the `withSSL`
method of a cluster builder instance.

```php
$sslBuilder = new \CassandraNative\SSL\SSLBuilder();
$sslBuilder
    ->withClientCert(__DIR__ . '/certs/localhost.cer')
    ->withPrivateKey(__DIR__ . '/certs/localhost.key.pem');
    ->withTrustedCerts(__DIR__ . '/certs/localhost.cer.pem');

$clusterBuilder->withSSL($sslBuilder->build());
```

### Compression

Compression can be enabled by calling the `withCompression` method
on the cluster builder.

```php
$clusterBuilder->withCompression(true);
```

The client will check to see if either the snappy or LZ4 extensions
are installed and picks the one that is available. If both are 
available it will pick LZ4 over Snappy. If neither are avilable
the builder will throw an exception when you try to build the 
cluster.

### Statements

The client currently only supports two types of statements, simple
and prepared. Call statements are executed via the `execute` method
which accepts the statment, an optional array of bound values and 
an optional consistency level which overrides the default.

The `execute` method returns a `Rows` class which acts like an 
array and can be interated over.

#### Simple Statements

Simple statements use the `SimpleStatement` class.

```php
$stmt = new \CassandraNative\Statement\SimpleStatement('DESCRIBE TABLES');
$rows = $cassandra->execute($stmt);
```

Simple statements support parameterised values.

```php
$stmt = new \CassandraNative\Statement\SimpleStatement('SELECT col1, col2, col3 FROM my_table WHERE id=?')
$rows = $cassandra->execute(
    $stmt,
    [
        [1001, Cassandra::COLUMNTYPE_BIGINT]
    ]
);

// Or

$stmt = new \CassandraNative\Statement\SimpleStatement('SELECT col1, col2, col3 FROM my_table WHERE id=:id')
$rows = $cassandra->execute(
    $stmt,
    [
        'id' => [1001, Cassandra::COLUMNTYPE_BIGINT]
    ]
);
```

You must specify the bound parameters type when using a simple 
statement

#### Prepared Statements

Prepared Statements are created via the `prepare` method.

```php
$stmt = $cassandra->prepare('UPDATE my_table SET col2=?,col3=? WHERE col1=?');
$values = ['col2' => 5, 'col3' => '0x55', 'col1' => 'five'];
$rows = $cassandra->execute($stmt, $values);
```

Unlike Simple Statements, you don't need to specify the bound values
type.

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
