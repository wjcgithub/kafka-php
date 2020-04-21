<?php
declare(strict_types=1);

namespace Kafka;

use function fclose;
use function is_resource;
use function rewind;

class SocketCoroutine extends SwooleSocket
{
    public function connect(): void
    {
        if (is_resource($this->stream)) {
            return;
        }

        $this->createStream();
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    public function isResource(): bool
    {
        return is_resource($this->stream);
    }
}
