<?php
declare(strict_types=1);

namespace Kafka;

use function is_resource;
use function sprintf;
use function strlen;
use function trim;

class SwooleSocket
{
    public const READ_MAX_LENGTH = 5242880; // read socket max length 5MB

    /**
     * max write socket buffer
     * fixed:send of 8192 bytes failed with errno=11 Resource temporarily
     * fixed:'fwrite(): send of ???? bytes failed with errno=35 Resource temporarily unavailable'
     * unavailable error info
     */
    public const MAX_WRITE_BUFFER = 2048;

    /**
     * @var int Connect timeout in seconds.
     */
    protected $connectTimeoutSec = 0;

    /**
     * @var int Connect timeout in microseconds.
     */
    protected $connectTimeoutUsec = 100000;

    /**
     * Send timeout in seconds.
     *
     * @var int
     */
    protected $sendTimeoutSec = 0;

    /**
     * Send timeout in microseconds.
     *
     * @var int
     */
    protected $sendTimeoutUsec = 100000;

    /**
     * Recv timeout in seconds
     *
     * @var int
     */
    protected $recvTimeoutSec = 10;

    /**
     * Recv timeout in microseconds
     *
     * @var int
     */
    protected $recvTimeoutUsec = 750000;

    /**
     * @var \Swoole\Coroutine\Socket
     */
    protected $stream;

    /**
     * @var string|null
     */
    protected $host;

    /**
     * @var int
     */
    protected $port = -1;

    /**
     * @var int
     */
    protected $maxWriteAttempts = 3;

    /**
     * @var Config|null
     */
    protected $config;

    /**
     * @var SaslMechanism|null
     */
    private $saslProvider;

    public function __construct(string $host, int $port, ?Config $config = null, ?SaslMechanism $saslProvider = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->config = $config;
        $this->saslProvider = $saslProvider;
    }

    public function setConnectTimeoutSec(int $sendTimeoutSec): void
    {
        $this->sendTimeoutSec = $sendTimeoutSec;
    }

    public function setConnectTimeoutUsec(int $sendTimeoutUsec): void
    {
        $this->sendTimeoutUsec = $sendTimeoutUsec;
    }

    public function setSendTimeoutSec(int $sendTimeoutSec): void
    {
        $this->sendTimeoutSec = $sendTimeoutSec;
    }

    public function setSendTimeoutUsec(int $sendTimeoutUsec): void
    {
        $this->sendTimeoutUsec = $sendTimeoutUsec;
    }

    public function setRecvTimeoutSec(int $recvTimeoutSec): void
    {
        $this->recvTimeoutSec = $recvTimeoutSec;
    }

    public function setRecvTimeoutUsec(int $recvTimeoutUsec): void
    {
        $this->recvTimeoutUsec = $recvTimeoutUsec;
    }

    public function setMaxWriteAttempts(int $number): void
    {
        $this->maxWriteAttempts = $number;
    }

    public function getConnectTimeout()
    {
        return $this->connectTimeoutSec + ($this->connectTimeoutUsec / 1000000);
    }

    public function getReadTimeout()
    {
        return $this->sendTimeoutSec + ($this->sendTimeoutUsec / 1000000);
    }

    public function getWriteTimeout()
    {
        return $this->recvTimeoutSec + ($this->recvTimeoutUsec / 1000000);
    }

    /**
     * @throws Exception
     */
    protected function createStream(): void
    {
        if (trim($this->host) === '') {
            throw new Exception('Cannot open null host.');
        }

        if ($this->port <= 0) {
            throw new Exception('Cannot open without port.');
        }

        $socket = new \Swoole\Coroutine\Socket(AF_INET, SOCK_STREAM, 0);

        if ($this->config !== null && $this->config->getSslEnable()) { // ssl connection
            $socket->setProtocol([
                'open_ssl' => true,
                'ssl_cert_file' => $this->config->getSslLocalCert(),
                'ssl_key_file' => $this->config->getSslPassphrase()
            ]);
        }
        $rest = $socket->connect($this->host, $this->port, $this->getConnectTimeout());
        $this->stream = $socket;
        if (!$rest) {
            throw new Exception(
                sprintf('Could not connect to %s:%d (%s [%d])', $this->host, $this->port, $this->stream->errMsg, $this->stream->errCode)
            );
        }

        // SASL auth
        if ($this->saslProvider !== null) {
            $this->saslProvider->authenticate($this);
        }
    }

    /**
     * @return \Swoole\Coroutine\Socket
     */
    public function getSocket()
    {
        return $this->stream;
    }

    /**
     * Read from the socket at most $len bytes.
     *
     * This method will not wait for all the requested data, it will return as
     * soon as any data is received.
     *
     * @throws Exception
     */
    public function read(int $length): string
    {
        if ($length > self::READ_MAX_LENGTH) {
            throw Exception\Socket::invalidLength($length, self::READ_MAX_LENGTH);
        }
        $data = $this->stream->recvAll($length, $this->getReadTimeout());
        if ($data === false) {
            throw new Exception(
                sprintf('Read msg error %s:%d (%s [%d])', $this->host, $this->port, $this->stream->errMsg, $this->stream->errCode)
            );
        }
        return $data;
    }

    /**
     * Write to the socket.
     *
     * @throws Exception
     */
    public function write(string $buffer): int
    {
        if ($buffer === null) {
            throw new Exception('You must inform some data to be written');
        }

        $bytesToWrite = strlen($buffer);

        $hasWriteLen = $this->stream->sendAll($buffer, $this->getWriteTimeout());

        return $hasWriteLen;
    }
}
