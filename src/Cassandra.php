<?php

//TODO:
// Implement Tuple
// Implement UDT

namespace CassandraNative;

use CassandraNative\Cluster\ClusterOptions;
use CassandraNative\Connection\Socket;
use CassandraNative\Exception\AuthenticationException;
use CassandraNative\Exception\CassandraException;
use CassandraNative\Exception\ProtocolException;
use CassandraNative\Exception\QueryException;
use CassandraNative\Exception\ServerException;
use CassandraNative\Exception\TimeoutException;
use CassandraNative\Exception\UnauthorizedException;
use CassandraNative\Result\Rows;
use CassandraNative\Statement\PreparedStatement;
use CassandraNative\Statement\SimpleStatement;
use CassandraNative\Statement\StatementInterface;

/**
 * Cassanda Connector
 *
 * A native Cassandra connector for PHP based on the CQL binary protocol v3,
 * without the need for any external extensions.
 *
 * Requires PHP version >8, and Cassandra >1.2.
 *
 * Usage and more information is found on README.md
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2023 Uri Hartmann
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @category  Database
 * @package   Cassandra
 * @author    Uri Hartmann
 * @copyright 2023 Uri Hartmann
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 * @version   2023.07.08
 * @link      https://www.humancodes.org/projects/php-cql
 */

class Cassandra
{
    const CONSISTENCY_ANY          = 0x0000;
    const CONSISTENCY_ONE          = 0x0001;
    const CONSISTENCY_TWO          = 0x0002;
    const CONSISTENCY_THREE        = 0x0003;
    const CONSISTENCY_QUORUM       = 0x0004;
    const CONSISTENCY_ALL          = 0x0005;
    const CONSISTENCY_LOCAL_QUORUM = 0x0006;
    const CONSISTENCY_EACH_QUORUM  = 0x0007;
    const CONSISTENCY_LOCAL_ONE    = 0x000A;

    const COLUMNTYPE_CUSTOM    = 0x0000;
    const COLUMNTYPE_ASCII     = 0x0001;
    const COLUMNTYPE_BIGINT    = 0x0002;
    const COLUMNTYPE_BLOB      = 0x0003;
    const COLUMNTYPE_BOOLEAN   = 0x0004;
    const COLUMNTYPE_COUNTER   = 0x0005;
    const COLUMNTYPE_DECIMAL   = 0x0006;
    const COLUMNTYPE_DOUBLE    = 0x0007;
    const COLUMNTYPE_FLOAT     = 0x0008;
    const COLUMNTYPE_INT       = 0x0009;
    const COLUMNTYPE_TEXT      = 0x000A;
    const COLUMNTYPE_TIMESTAMP = 0x000B;
    const COLUMNTYPE_UUID      = 0x000C;
    const COLUMNTYPE_VARCHAR   = 0x000D;
    const COLUMNTYPE_VARINT    = 0x000E;
    const COLUMNTYPE_TIMEUUID  = 0x000F;
    const COLUMNTYPE_INET      = 0x0010;
    const COLUMNTYPE_LIST      = 0x0020;
    const COLUMNTYPE_MAP       = 0x0021;
    const COLUMNTYPE_SET       = 0x0022;

    const OPCODE_ERROR          = 0x00;
    const OPCODE_STARTUP        = 0x01;
    const OPCODE_READY          = 0x02;
    const OPCODE_AUTHENTICATE   = 0x03;
    const OPCODE_CREDENTIALS    = 0x04;
    const OPCODE_OPTIONS        = 0x05;
    const OPCODE_SUPPORTED      = 0x06;
    const OPCODE_QUERY          = 0x07;
    const OPCODE_RESULT         = 0x08;
    const OPCODE_PREPARE        = 0x09;
    const OPCODE_EXECUTE        = 0x0A;
    const OPCODE_REGISTER       = 0x0B;
    const OPCODE_EVENT          = 0x0C;
    const OPCODE_BATCH          = 0x0D;
    const OPCODE_AUTH_CHALLENGE = 0x0E;
    const OPCODE_AUTH_RESPONSE  = 0x0F;
    const OPCODE_AUTH_SUCCESS   = 0x10;

    const BATCH_LOGGED          = 0x00;
    const BATCH_UNLOGGED        = 0x01;
    const BATCH_COUNTER         = 0x02;

    const RESULT_KIND_VOID          = 0x001;
    const RESULT_KIND_ROWS          = 0x002;
    const RESULT_KIND_SET_KEYSPACE  = 0x003;
    const RESULT_KIND_PREPARED      = 0x004;
    const RESULT_KIND_SCHEMA_CHANGE = 0x005;

    const FLAG_COMPRESSION    = 0x01;
    const FLAG_TRACING        = 0x02;
    const FLAG_CUSTOM_PAYLOAD = 0x04;
    const FLAG_WARNING        = 0x08;

    const PROTOCOL_VERSION  = 4;

    protected Socket $socket;

    protected string $fullFrame = '';

    protected int $defaultConsistency;

    /**
     * @param ClusterOptions $options
     * @throws CassandraException
     */
    public function __construct(ClusterOptions $options)
    {
        $this->socket = new Socket();
        $this->defaultConsistency = $options->getDefaultConsistency();
        $this->establishConnection($options);
    }

    /**
     * Establishes a connection with a cassandra host based on options provided
     * by the ClusterBuilder
     * 
     * @param ClusterOptions $clusterOptions
     * 
     * @throws CassandraException
     */
    protected function establishConnection(ClusterOptions $clusterOptions): void
    {
        $this->socket->connect($clusterOptions);

        // Don't send startup & authentication if we're using a persistent connection
        if ($this->socket->isPersistent()) {
            return;
        }

        // Writes a STARTUP frame
        $frameBody = $this->packStringMap(['CQL_VERSION' => '4.0.0']);
        $this->writeFrame(self::OPCODE_STARTUP, $frameBody);

        // Reads incoming frame - should be immediate do we don't
        $frame = $this->readFrame();
        $opcode = $frame['opcode'];

        // Checks if an AUTHENTICATE frame was received
        if ($opcode == self::OPCODE_AUTHENTICATE) {
            // Writes a CREDENTIALS frame
            $body =
                $this->packShort(2) .
                $this->packString('username') .
                $this->packString($clusterOptions->getUsername()) .
                $this->packString('password') .
                $this->packString($clusterOptions->getPassword());
            $this->writeFrame(self::OPCODE_CREDENTIALS, $body);

            // Reads incoming frame
            $frame = $this->readFrame();
            $opcode = $frame['opcode'];
        }

        // Checks if a READY frame was received
        if ($opcode != self::OPCODE_READY) {
            throw new ProtocolException('Missing READY packet. Got ' . $opcode . ' instead', $opcode);
        }
    }

    /**
     * Connects the client to the specified keyspace. Same as using a 
     * USE $keyspace query
     * 
     * @param string $keyspace
     * 
     * @throws CassandraException
     */
    public function connect(string $keyspace): void
    {
        $stmt = new SimpleStatement('USE ' . $keyspace);
        $this->execute($stmt);
    }

    /**
     * Closes an open client connection.
     */
    public function close(): void
    {
        $this->socket->close();
    }

    /**
     * Queries the database using the given CQL.
     *
     * @param StatementInterface $stmt The query to run.
     * @param array $values            Values to bind in a sequential or key=>value format,
     *                                 where key is the column's name.
     * @param ?int $consistency        Consistency level for the operation.
     * 
     * @return Rows Result of the query. Might be an array of rows (for
     *              SELECT), or the operation's result (for USE, CREATE,
     *              ALTER, UPDATE).
     * 
     * @throws CassandraException
     */
    public function execute(
        StatementInterface $stmt,
        array $values = [],
        ?int $consistency = null
    ): Rows {
        $consistency ??= $this->defaultConsistency;

        $rows = match (true) {
            $stmt instanceof PreparedStatement => $this->executePreparedStatement($stmt, $values, $consistency),
            $stmt instanceof SimpleStatement => $this->executeSimpleStatement($stmt, $values, $consistency)
        };

        return new Rows($rows);
    }

    /**
     * Prepares a query statement.
     *
     * @param string $cql The query to prepare.
     *
     * @return PreparedStatement The statement's information to be used with the execute
     *                           method.
     *
     * @throws CassandraException
     */
    public function prepare(string $cql): PreparedStatement
    {
        // Prepares the frame's body
        $frame = $this->packLongString($cql);

        // Writes a PREPARE frame and return the result
        $retval = $this->requestResult(self::OPCODE_PREPARE, $frame);

        return new PreparedStatement($retval['id'], $retval['columns']);
    }

    /**
     * Executes a prepared statement.
     *
     * @param PreparedStatement $stmt The prepared statement as returned from the
     *                                prepare method.
     * @param array $values           Bind values for the prepared statement 
     * @param int $consistency        Consistency level for the operation.
     *
     * @return array Result of the execution. Might be an array of rows (for
     *               SELECT), or the operation's result (for USE, CREATE,
     *               ALTER, UPDATE).
     *
     * @throws CassandraException
     */
    protected function executePreparedStatement(
        PreparedStatement $stmt,
        array $values,
        int $consistency
    ): array {
        // Prepares the frame's body - <id><count><values map>
        $frame = base64_decode($stmt->getId());
        $frame = $this->packString($frame) .
            $this->packShort($consistency) .
            $this->packByte(0x01) . // values only
            $this->packShort(count($values));

        foreach ($stmt->getColumns() as $key => $column) {
            $value = $values[$key];

            $data = $this->packValue(
                $value,
                $column['type'],
                $column['subtype1'],
                $column['subtype2']
            );

            $frame .= $this->packLongString($data);
        }

        // Writes a EXECUTE frame and return the result
        return $this->requestResult(self::OPCODE_EXECUTE, $frame);
    }

    /**
     * Executes a simple statement
     * 
     * @param SimpleStatement $stmt The statement to be run.
     * @param array $values         Values to bind to the statement being run.
     * @param int $consistency      Consistency level for the operation.
     * 
     * @return array Result of the execution. Might be an array of rows (for
     *               SELECT), or the operation's result (for USE, CREATE,
     *               ALTER, UPDATE).
     * 
     * @throws CassandraException
     */
    protected function executeSimpleStatement(
        SimpleStatement $stmt,
        array $values,
        int $consistency
    ): array {
        // Prepares the frame's body
        // TODO: Support the new <flags> byte
        $frame = $this->packLongString($stmt->getStatement()) . $this->packShort($consistency);
        if (count($values)) {
            $values_data = '';
            $namedParameters = false;
            foreach ($values as $key => $value) {
                $namedParameters = $namedParameters || is_string($key);

                if ($namedParameters) {
                    $values_data .= $this->packString($key);
                }

                $data = $this->packValue($value[0], $value[1], 0, 0);

                $values_data .= $this->packLongString($data);
            }

            $frame .= $this->packByte(0x01 | ($namedParameters ? 0x40 : 0x00)) .
                $this->packShort(count($values)) .
                $values_data;
        } else {
            $frame .= $this->packByte(0x00);
        }

        return $this->requestResult(self::OPCODE_QUERY, $frame);
    }

    /**
     * Writes a (QUERY/PREPARE/EXCUTE) frame, reads the result, and parses it.
     *
     * @param int    $opcode Frame's opcode.
     * @param string $body   Frame's body.
     *
     * @return array Result of the request. Might be an array of rows (for
     *               SELECT), or the operation's result (for USE, CREATE,
     *               ALTER, UPDATE).
     *
     * @throws CassandraException
     */
    protected function requestResult(int $opcode, string $body): array
    {
        // Writes the frame
        $this->writeFrame($opcode, $body);

        // Reads incoming frame
        $frame = $this->readFrame();
        $opcode = $frame['opcode'];

        // Parses the incoming frame
        if ($opcode == self::OPCODE_RESULT) {
            return $this->parseResult($frame['body']);
        }

        throw new ProtocolException('Unknown opcode returned from cassandra', $opcode);
    }

    /**
     * Packs and writes a frame to the socket.
     *
     * @param int $opcode   Frame's opcode.
     * @param string $body  Frame's body.
     * @param int $response Frame's response flag.
     * @param int $stream   Frame's stream id.
     *
     * @throws CassandraException
     */
    protected function writeFrame(int $opcode, string $body, int $response = 0, int $stream = 0): void
    {
        // Prepares the outgoing packet
        $frame = $this->packFrame($opcode, $body, $response, $stream);

        // Writes frame to socket
        $this->socket->writeFrame($frame);
    }

    /**
     * Converts an error message returned from Cassandra into an exception
     * 
     * @param int $errorCode       The error code returned from cassandra
     * @param string $errorMessage The error message returned from cassandra
     * 
     * @throws CassandraException
     */
    protected function convertCassandraErrorToException(int $errorCode, string $errorMessage): void
    {
        $exception = match ($errorCode) {
            0x0000, 0x1001, 0x1002, 0x1003 => ServerException::class,
            0x000A => ProtocolException::class,
            0x0100 => AuthenticationException::class,
            0x1100, 0x1200 => TimeoutException::class,
            0x1300, 0x1400, 0x1500, 0x2000, 0x2200, 0x2300, 0x2400, 0x2500 => QueryException::class,
            0x2100 => UnauthorizedException::class
        };

        throw new $exception($errorMessage, $errorCode);
    }

    /**
     * Parses a returned Frame send back by cassandra. Checks for any errors returned 
     * and throws an exception
     * 
     * @param string $header The returned frame header
     * @param string $body   The returned frame body
     * 
     * @return array         The parsed frame converted into an opcode and body
     * 
     * @throws CassandraException
     */
    protected function parseIncomingFrame(string $header, string $body): array
    {
        $flags = ord($header[1]);

        // Unpack the header to its contents:
        // <byte version><byte flags><uint16 stream><byte opcode><int length>
        $opcode = ord($header[4]);

        $this->fullFrame = $header . $body;

        if ($flags & self::FLAG_WARNING) {
            $iPos = 0;
            $warningCount = $this->popShort($body, $iPos);
            for ($i = 0; $i < $warningCount; $i++) {
                $value = $this->popString($body, $iPos);
            }

            $body = substr($body, $iPos);
        }

        // If we got an error - trigger it and return an error
        if ($opcode == self::OPCODE_ERROR) {
            // ERROR: <int code><string msg>
            $errCode = $this->intFromBin($body, 0, 4);
            $bodyOffset = 4;  // Must be passed by reference
            $errMsg = $this->popString($body, $bodyOffset);
            $this->convertCassandraErrorToException($errCode, $errMsg);
        }

        return [
            'opcode' => $opcode,
            'body' => $body
        ];
    }

    /**
     * Reads pending frame from the socket.
     *
     * @return array Incoming data.
     *
     * @throws CassandraException
     */
    protected function readFrame(): array
    {
        // Read the 9 bytes header
        $header = $this->socket->read(9);
        $length = $this->intFromBin($header, 5, 4, 0);

        // Read frame body, if exists
        $body = '';
        
        if ($length) {
            $body = $this->socket->read($length);
        }

        return $this->parseIncomingFrame($header, $body);
    }

    /**
     * Parses a RESULT frame.
     *
     * @param string $body Frame's body
     *
     * @return array       Parsed frame. Might be an array of rows (for SELECT),
     *                     or the operation's result (for USE, CREATE, ALTER,
     *                     UPDATE).
     *
     * @throws CassandraException
     */
    protected function parseResult(string $body): array
    {
        // Parse RESULTS opcode
        $bodyOffset = 0;
        $kind = $this->popInt($body, $bodyOffset);

        switch ($kind) {
            case self::RESULT_KIND_VOID:
                return [['result' => 'success']];
            case self::RESULT_KIND_ROWS:
                return $this->parseRows($body, $bodyOffset);
            case self::RESULT_KIND_SET_KEYSPACE:
                $keyspace = $this->popString($body, $bodyOffset);
                return [['keyspace' => $keyspace]];
            case self::RESULT_KIND_PREPARED:
                // <string id><metadata>
                $id = base64_encode($this->popString($body, $bodyOffset));
                $metadata = $this->parseRowsMetadata($body, $bodyOffset, true);
                $columns = [];

                foreach ($metadata as $column) {
                    $columns[$column['name']] = [
                        'type' => $column['type'],
                        'subtype1' => $column['subtype1'],
                        'subtype2' => $column['subtype2']
                    ];
                }

                return ['id' => $id, 'columns' => $columns];
            case self::RESULT_KIND_SCHEMA_CHANGE:
                // <string change><string keyspace><string table>
                $change = $this->popString($body, $bodyOffset);
                $target = $this->popString($body, $bodyOffset);
                $options = $this->popString($body, $bodyOffset);
                return [[
                    'change' => $change, 'target' => $target,
                    'options' => $options
                ]];
        }

        throw new ProtocolException('Unknown result kind ' . $kind . ' full frame: ' . bin2hex($this->fullFrame));
    }

    /**
     * Parses a RESULT Rows metadata (also used for RESULT Prepared), starting
     * from the offset, and advancing it in the process.
     *
     * @param string $body    Metadata body.
     * @param int $bodyOffset Metadata body offset to start from.
     *
     * @return array Columns list
     */
    protected function parseRowsMetadata(string $body, int &$bodyOffset, bool $readPk = false): array
    {
        $flags = $this->popInt($body, $bodyOffset);
        $columns_count = $this->popInt($body, $bodyOffset);

        if ($readPk) {
            $pk_count = $this->popInt($body, $bodyOffset);

            for ($i = 0; $i < $pk_count; $i++) {
                $this->popShort($body, $bodyOffset);
            }
        }

        $global_table_spec = ($flags & 0x0001);
        if ($global_table_spec) {
            $keyspace = $this->popString($body, $bodyOffset);
            $table = $this->popString($body, $bodyOffset);
        }

        $columns = [];

        for ($i = 0; $i < $columns_count; $i++) {
            if (!$global_table_spec) {
                $keyspace = $this->popString($body, $bodyOffset);
                $table = $this->popString($body, $bodyOffset);
            }

            $column_name = $this->popString($body, $bodyOffset);
            $column_type = $this->popShort($body, $bodyOffset);
            if ($column_type == self::COLUMNTYPE_CUSTOM) {
                $column_type = $this->popString($body, $bodyOffset);
                $column_subtype1 = 0;
                $column_subtype2 = 0;
            } elseif (($column_type == self::COLUMNTYPE_LIST) ||
                ($column_type == self::COLUMNTYPE_SET)
            ) {
                $column_subtype1 = $this->popShort($body, $bodyOffset);
                if ($column_subtype1 == self::COLUMNTYPE_CUSTOM) {
                    $column_subtype1 = $this->popString($body, $bodyOffset);
                }
                $column_subtype2 = 0;
            } elseif ($column_type == self::COLUMNTYPE_MAP) {
                $column_subtype1 = $this->popShort($body, $bodyOffset);
                if ($column_subtype1 == self::COLUMNTYPE_CUSTOM) {
                    $column_subtype1 = $this->popString($body, $bodyOffset);
                }

                $column_subtype2 = $this->popShort($body, $bodyOffset);
                if ($column_subtype2 == self::COLUMNTYPE_CUSTOM) {
                    $column_subtype2 = $this->popString($body, $bodyOffset);
                }
            } else {
                $column_subtype1 = 0;
                $column_subtype2 = 0;
            }
            $columns[] = [
                'keyspace' => $keyspace,
                'table' => $table,
                'name' => $column_name,
                'type' => $column_type,
                'subtype1' => $column_subtype1,
                'subtype2' => $column_subtype2
            ];
        }
        return $columns;
    }

    /**
     * Parses a RESULT Rows kind.
     *
     * @param string $body    Frame body to parse.
     * @param int $bodyOffset Offset to start from.
     *
     * @return array Rows with associative array of the records.
     *
     * @throws CassandraException
     */
    protected function parseRows(string $body, int $bodyOffset): array
    {
        // <metadata><int count><rows_content>
        $columns = $this->parseRowsMetadata($body, $bodyOffset);

        $rows_count = $this->popInt($body, $bodyOffset);

        $retval = [];
        for ($i = 0; $i < $rows_count; $i++) {
            $row = [];
            foreach ($columns as $col) {
                $content = $this->popBytes($body, $bodyOffset);
                $value = $this->unpackValue(
                    $content,
                    $col['type'],
                    $col['subtype1'],
                    $col['subtype2']
                );

                $row[$col['name']] = $value;
            }
            $retval[] = $row;
        }

        return $retval;
    }

    /**
     * Packs a value to its binary form based on a column type. Used for
     * prepared statement.
     *
     * @param mixed $value  Value to pack.
     * @param int $type     Column type.
     * @param int $subtype1 Sub column type for list/set or key for map.
     * @param int $subtype2 Sub column value type for map.
     *
     * @return string Binary form of the value.
     *
     * @throws \InvalidArgumentException
     */
    protected function packValue(mixed $value, int $type, int $subtype1 = 0, int $subtype2 = 0): string
    {
        return match ($type) {
            self::COLUMNTYPE_CUSTOM, self::COLUMNTYPE_BLOB => $this->packBlob($value),
            self::COLUMNTYPE_ASCII, self::COLUMNTYPE_TEXT, self::COLUMNTYPE_VARCHAR => $value,
            self::COLUMNTYPE_BIGINT, self::COLUMNTYPE_COUNTER, self::COLUMNTYPE_TIMESTAMP => $this->packBigint($value),
            self::COLUMNTYPE_BOOLEAN => $this->packBoolean($value),
            self::COLUMNTYPE_DECIMAL => $this->packDecimal($value),
            self::COLUMNTYPE_DOUBLE => $this->packDouble($value),
            self::COLUMNTYPE_FLOAT => $this->packFloat($value),
            self::COLUMNTYPE_INT => $this->packInt($value),
            self::COLUMNTYPE_UUID, self::COLUMNTYPE_TIMEUUID => $this->packUuid($value),
            self::COLUMNTYPE_VARINT => $this->packVarInt($value),
            self::COLUMNTYPE_INET => $this->packInet($value),
            self::COLUMNTYPE_LIST, self::COLUMNTYPE_SET => $this->packList($value, $subtype1),
            self::COLUMNTYPE_MAP => $this->packMap($value, $subtype1, $subtype2),
            default => throw new \InvalidArgumentException('Unknown column type ' . $type)
        };
    }

    /**
     * Unpacks a value from its binary form based on a column type. Used for
     * parsing rows.
     *
     * @param ?string $content Content to unpack.
     * @param int $type        Column type.
     * @param int $subtype1    Sub column type for list/set or key for map.
     * @param int $subtype2    Sub column value type for map.
     *
     * @return mixed The unpacked value.
     *
     * @throws CassandraException
     */
    protected function unpackValue(?string $content, int $type, int $subtype1 = 0, int $subtype2 = 0): mixed
    {
        if ($content === NULL) {
            return NULL;
        }

        return match ($type) {
            self::COLUMNTYPE_CUSTOM, self::COLUMNTYPE_BLOB => $this->unpackBlob($content),
            self::COLUMNTYPE_ASCII, self::COLUMNTYPE_TEXT, self::COLUMNTYPE_VARCHAR => $content,
            self::COLUMNTYPE_BIGINT, self::COLUMNTYPE_COUNTER, self::COLUMNTYPE_TIMESTAMP => $this->unpackBigint($content),
            self::COLUMNTYPE_BOOLEAN => $this->unpackBoolean($content),
            self::COLUMNTYPE_DECIMAL => $this->unpackDecimal($content),
            self::COLUMNTYPE_DOUBLE => $this->unpackDouble($content),
            self::COLUMNTYPE_FLOAT => $this->unpackFloat($content),
            self::COLUMNTYPE_INT => $this->unpackInt($content),
            self::COLUMNTYPE_UUID, self::COLUMNTYPE_TIMEUUID => $this->unpackUuid($content),
            self::COLUMNTYPE_VARINT => $this->unpackVarInt($content),
            self::COLUMNTYPE_INET => $this->unpackInet($content),
            self::COLUMNTYPE_LIST, self::COLUMNTYPE_SET => $this->unpackList($content, $subtype1),
            self::COLUMNTYPE_MAP => $this->unpackMap($content, $subtype1, $subtype2),
            default => throw new ProtocolException('Unknown column type returned from cassandra ' . $type)
        };
    }

    /**
     * Packs a COLUMNTYPE_BLOB value to its binary form.
     *
     * @param string $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packBlob(string $value): string
    {
        if (str_starts_with($value, '0x')) {
            $value = pack('H*', substr($value, 2));
        }
        return $value;
    }

    /**
     * Unpacks a COLUMNTYPE_BLOB value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return string Unpacked value in hexadecimal representation.
     */
    protected function unpackBlob(string $content, string $prefix = '0x'): string
    {
        $value = unpack('H*', $content);
        if ($value[1]) {
            $value[1] = $prefix . $value[1];
        }
        return $value[1];
    }

    /**
     * Packs a COLUMNTYPE_BIGINT value to its binary form.
     *
     * @param int $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packBigint(int $value): string
    {
        return $this->binFromInt($value, 8, 1);
    }

    /**
     * Unpacks a COLUMNTYPE_BIGINT value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return int Unpacked value.
     */
    protected function unpackBigint(string $content): int
    {
        return $this->intFromBin($content, 0, 8, 1);
    }

    /**
     * Packs a COLUMNTYPE_BOOLEAN value to its binary form.
     *
     * @param ?bool $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packBoolean(?bool $value): string
    {
        if ($value === NULL) {
            return '';
        }

        return ($value)? chr(1) : chr(0);
    }

    /**
     * Unpacks a COLUMNTYPE_BOOLEAN value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return ?bool Unpacked value.
     */
    protected function unpackBoolean(string $content): ?bool
    {
        if (strlen($content) > 0) {
            $c = ord($content[0]);
            if ($c == 1) {
                return true;
            } elseif ($c == 0) {
                return false;
            } else {
                return null;
            }
        }

        return null;
    }

    /**
     * Packs a COLUMNTYPE_DECIMAL value to its binary form.
     *
     * @param float|int $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packDecimal(float|int $value): string
    {
        // Based on http://docs.oracle.com/javase/7/docs/api/java/math/BigDecimal.html

        // Find the scale
        $value1 = abs($value);
        $positiveScale = 0;
        while (floor($value1) && (fmod($value1, 10) == 0)) {
            $value1 /= 10;
            $positiveScale++;
        }

        $value1 = $value;
        $negativeScale = 0;
        while (fmod($value1, 1)) {
            $value1 *= 10;
            $negativeScale--;
        }

        $scale = $negativeScale ? -$negativeScale : -$positiveScale;
        $unscaledValue = $value / pow(10, -$scale);

        return $this->packInt($scale) . $this->packVarInt($unscaledValue);
    }

    /**
     * Unpacks a COLUMNTYPE_DECIMAL value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return float|int Unpacked value.
     */
    protected function unpackDecimal(string $content): float|int
    {
        // Based on http://docs.oracle.com/javase/7/docs/api/java/math/BigDecimal.html

        $len = strlen($content);
        if ($len < 5) {
            return 0;
        }

        $data = unpack('N', $content);
        $scale = $data[1];
        $unscaledValue = $this->unpackVarInt(substr($content, 4));

        return $unscaledValue * pow(10, -$scale);
    }

    /**
     * Packs a COLUMNTYPE_DOUBLE value to its binary form.
     *
     * @param double $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packDouble(float $value): string
    {
        $littleEndian = pack('d', $value);
        $retval = '';
        for ($i = 7; $i >= 0; $i--) {
            $retval .= $littleEndian[$i];
        }
        return $retval;
    }

    /**
     * Unpacks a COLUMNTYPE_DOUBLE value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return double Unpacked value.
     */
    protected function unpackDouble(string $content): float
    {
        $bigEndian = '';
        for ($i = 7; $i >= 0; $i--) {
            $bigEndian .= $content[$i];
        }

        $value = unpack('d', $bigEndian);
        return $value[1];
    }

    /**
     * Packs a COLUMNTYPE_FLOAT value to its binary form.
     *
     * @param float $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packFloat(float $value): string
    {
        $littleEndian = pack('f', $value);
        $retval = '';
        for ($i = 3; $i >= 0; $i--) {
            $retval .= $littleEndian[$i];
        }
        return $retval;
    }

    /**
     * Unpacks a COLUMNTYPE_FLOAT value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return float Unpacked value.
     */
    protected function unpackFloat(string $content): float
    {
        $bigEndian = '';
        for ($i = 3; $i >= 0; $i--) {
            $bigEndian .= $content[$i];
        }

        $value = unpack('f', $bigEndian);
        return $value[1];
    }

    /**
     * Packs a COLUMNTYPE_INT value to its binary form.
     *
     * @param int $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packInt(int $value): string
    {
        return $this->binFromInt($value, 4, 1);
    }

    /**
     * Unpacks a COLUMNTYPE_INT value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return int Unpacked value.
     */
    protected function unpackInt(string $content): int
    {
        return $this->intFromBin($content, 0, 4, 1);
    }

    /**
     * Packs a COLUMNTYPE_UUID value to its binary form.
     *
     * @param string $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packUuid(string $value): string
    {
        return pack('H*', str_replace('-', '', $value));
    }

    /**
     * Unpacks a COLUMNTYPE_UUID value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return ?string Unpacked value.
     */
    protected function unpackUuid(string $content): ?string
    {
        $value = unpack('H*', $content);
        if ($value[1]) {
            return substr($value[1], 0, 8) . '-' . substr($value[1], 8, 4) . '-' .
                substr($value[1], 12, 4) . '-' . substr($value[1], 16, 4) . '-' .
                substr($value[1], 20);
        }

        return NULL;
    }

    /**
     * Packs a COLUMNTYPE_VARINT value to its binary form.
     *
     * @param int $content Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packVarInt(int $content): string
    {
        return $this->binFromInt($content, 0xFFFF, 1);
    }

    /**
     * Unpacks a COLUMNTYPE_VARINT value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return int Unpacked value.
     */
    protected function unpackVarInt(string $content): int
    {
        return $this->intFromBin($content, 0, strlen($content), 1);
    }

    /**
     * Packs a COLUMNTYPE_INET value to its binary form.
     *
     * @param string $value Value to pack.
     *
     * @return string Binary form of the value.
     */
    protected function packInet(string $value): string
    {
        return inet_pton($value);
    }

    /**
     * Unpacks a COLUMNTYPE_INET value from its binary form.
     *
     * @param string $content Content to unpack.
     *
     * @return int Unpacked value.
     */
    protected function unpackInet(string $content): int
    {
        return inet_ntop($content);
    }

    /**
     * Packs a COLUMNTYPE_LIST value to its binary form.
     *
     * @param array $value Value to pack.
     * @param int $subtype Values' Column type.
     *
     * @return string Binary form of the value.
     *
     * @throws \InvalidArgumentException
     */
    protected function packList(array $value, int $subtype): string
    {
        $retval = $this->packInt(count($value));

        foreach ($value as $item) {
            $itemPacked = $this->packValue($item, $subtype);
            $retval .= $this->packLongString($itemPacked);
        }

        return $retval;
    }

    /**
     * Unpacks a COLUMNTYPE_LIST value from its binary form.
     *
     * @param string $content Content to unpack.
     * @param int $subtype    Values' Column type.
     *
     * @return array Unpacked value.
     *
     * @throws ProtocolException
     */
    protected function unpackList(string $content, int $subtype): array
    {
        $contentOffset = 0;
        $itemsCount = $this->popInt($content, $contentOffset);
        $retval = [];
        for (; $itemsCount; $itemsCount--) {
            $subcontent = $this->popLongString($content, $contentOffset);
            $retval[] = $this->unpackValue($subcontent, $subtype);
        }

        return $retval;
    }

    /**
     * Packs a COLUMNTYPE_MAP value to its binary form.
     *
     * @param array $value  Value to pack.
     * @param int $subtype1 Keys' column type.
     * @param int $subtype2 Values' column type.
     *
     * @return string Binary form of the value.
     *
     * @throws \InvalidArgumentException
     */
    protected function packMap(array $value, int $subtype1, int $subtype2): string
    {
        $retval = $this->packInt(count($value));

        foreach ($value as $key => $item) {
            $keyPacked = $this->packValue($key, $subtype1);
            $itemPacked = $this->packValue($item, $subtype2);
            $retval .= $this->packLongString($keyPacked) .
                $this->packLongString($itemPacked);
        }

        return $retval;
    }

    /**
     * Unpacks a COLUMNTYPE_MAP value from its binary form.
     *
     * @param string $content Content to unpack.
     * @param int $subtype1   Keys' column type.
     * @param int $subtype2   Values' column type.
     *
     * @return array Unpacked value.
     *
     * @throws CassandraException
     */
    protected function unpackMap(string $content, int $subtype1, int $subtype2): array
    {
        $contentOffset = 0;
        $itemsCount = $this->popInt($content, $contentOffset);
        $retval = [];
        for (; $itemsCount; $itemsCount--) {
            $subKeyRaw = $this->popLongString($content, $contentOffset);
            $subValueRaw = $this->popLongString($content, $contentOffset);

            $subKey = $this->unpackValue($subKeyRaw, $subtype1);
            $subValue = $this->unpackValue($subValueRaw, $subtype2);
            $retval[$subKey] = $subValue;
        }

        return $retval;
    }

    /**
     * Pops a [bytes] value from the body, starting from the offset, and
     * advancing it in the process.
     *
     * @param string $body Content's body.
     * @param int &$offset Offset to start from.
     *
     * @return ?string Bytes content.
     */
    protected function popBytes(string $body, int &$offset): ?string
    {
        $stringLength = $this->intFromBin($body, $offset, 4, 0);

        if ($stringLength == 0xFFFFFFFF) {
            return NULL;
        }

        $retval = substr($body, $offset + 4, $stringLength);
        $offset += $stringLength + 4;

        return $retval;
    }

    /**
     * Pops a [string] value from the body, starting from the offset, and
     * advancing it in the process.
     *
     * @param string $body Content's body.
     * @param int &$offset Offset to start from.
     *
     * @return ?string String content.
     */
    protected function popString(string $body, int &$offset): ?string
    {
        $len = substr($body, $offset, 2);
        if (strlen($len) < 2) {
            return null;
        }

        $stringLength = unpack('n', substr($body, $offset, 2));
        if ($stringLength[1] == 0xFFFF) {
            $offset += 2;
            return null;
        }

        $retval = substr($body, $offset + 2, $stringLength[1]);
        $offset += $stringLength[1] + 2;
        return $retval;
    }

    /**
     * Pops a [long string] value from the body, starting from the offset, and
     * advancing it in the process.
     *
     * @param string $body Content's body.
     * @param int &$offset Offset to start from.
     *
     * @return ?string Long String content.
     */
    protected function popLongString(string $body, int &$offset): ?string
    {
        $stringLength = unpack('N', substr($body, $offset, 4));
        if ($stringLength[1] == 0xFFFFFFFF) {
            $offset += 4;
            return null;
        }

        $retval = substr($body, $offset + 4, $stringLength[1]);
        $offset += $stringLength[1] + 4;
        return $retval;
    }

    /**
     * Pops a [int] value from the body, starting from the offset, and
     * advancing it in the process.
     *
     * @param string $body Content's body.
     * @param int &$offset Offset to start from.
     *
     * @return int Int content.
     */
    protected function popInt(string $body, int &$offset): int
    {
        $retval = $this->intFromBin($body, $offset, 4, 1);
        $offset += 4;
        return $retval;
    }

    /**
     * Pops a [short] value from the body, starting from the offset, and
     * advancing it in the process.
     *
     * @param string $body Content's body.
     * @param int &$offset Offset to start from.
     *
     * @return int Short content.
     */
    protected function popShort(string $body, int &$offset): int
    {
        $retval = $this->intFromBin($body, $offset, 2, 1);
        $offset += 2;
        return $retval;
    }

    /**
     * Packs an outgoing frame.
     *
     * @param int $opcode   Frame's opcode.
     * @param string $body  Frame's body.
     * @param int $response Frame's response flag.
     * @param int $stream   Frame's stream id.
     *
     * @return string Frame's content.
     */
    protected function packFrame(int $opcode, string $body = '', int $response = 0, int $stream = 0): string
    {
        $version = ($response << 0x07) | self::PROTOCOL_VERSION;
        $flags = 0;

        return pack(
            'CCnCNa*',
            $version,
            $flags,
            $stream,
            $opcode,
            strlen($body),
            $body
        );
    }

    /**
     * Packs a [long string] notation (section 3)
     *
     * @param string $data String content.
     *
     * @return string Data content.
     */
    protected function packLongString(string $data): string
    {
        return pack('Na*', strlen($data), $data);
    }

    /**
     * Packs a [string] notation (section 3)
     *
     * @param string $data String content.
     *
     * @return string Data content.
     */
    protected function packString(string $data): string
    {
        return pack('na*', strlen($data), $data);
    }

    /**
     * Packs a [short] notation (section 3)
     *
     * @param int $data Short content.
     *
     * @return string Data content.
     */
    protected function packShort(int $data): string
    {
        return chr($data >> 0x08) . chr($data & 0xFF);
    }

    /**
     * Packs a [short] notation (missing from specs)
     *
     * @param int $data Byte content.
     *
     * @return string Data content.
     */
    protected function packByte(int $data): string
    {
        return chr($data);
    }

    /**
     * Packs a [string map] notation (section 3)
     *
     * @param array $dataArr Associative array of the map.
     *
     * @return string Data content.
     */
    protected function packStringMap(array $dataArr): string
    {
        $retval = pack('n', count($dataArr));
        foreach ($dataArr as $key => $value) {
            $retval .= $this->packString($key) . $this->packString($value);
        }
        return $retval;
    }

    /**
     * Converts binary format to a varint.
     *
     * @param string $data Binary content.
     * @param int $offset  Starting data offset.
     * @param int $length  Data length.
     * @param bool $signed Whether the returned data can be signed.
     *
     * @return int Parsed varint.
     */
    protected function intFromBin(string $data, int $offset, int $length, bool $signed = false): int
    {
        $len = strlen($data);

        if ((!$length) || ($offset >= $len)) {
            return 0;
        }

        $signed = $signed && (ord($data[$offset]) & 0x80);

        $value = 0;
        for ($i = 0; $i < $length; $i++) {
            $v = ord($data[$i + $offset]);
            if ($signed) {
                $v ^= 0xFF;
            }
            $value = $value * 256 + $v;
        }

        if ($signed) {
            $value = -($value + 1);
        }

        return $value;
    }

    /**
     * Converts varint to its binary format.
     *
     * @param int $value   Binary content.
     * @param int $length  Data length.
     * @param bool $signed Whether the returned data can be signed.
     *
     * @return string Binary content.
     */
    protected function binFromInt(int $value, int $length, bool $signed = false): string
    {
        $negative = (($signed) && ($value < 0));
        if ($negative) {
            $value = -($value + 1);
        }

        $retval = '';
        for ($i = 0; $i < $length; $i++) {
            $v = $value % 256;
            if ($negative) {
                $v ^= 0xFF;
            }
            $retval = chr($v) . $retval;
            $value = floor($value / 256);

            if (($length == 0xFFFF) && ($value == 0)) {
                break;
            }
        }

        return $retval;
    }
}
