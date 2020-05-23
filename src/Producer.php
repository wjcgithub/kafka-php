<?php
declare(strict_types=1);

namespace Kafka;

use Amp\Loop;
use Kafka\Producer\CoroutineProcess;
use Kafka\Producer\Process;
use Kafka\Producer\SyncProcess;
use Psr\Log\LoggerAwareTrait;
use function is_array;

class Producer
{
    use LoggerAwareTrait;
    use LoggerTrait;

    /**
     * @var Process|SyncProcess
     */
    private $process;

    public function __construct(?callable $producer = null)
    {
        $this->process = new CoroutineProcess();
    }

    /**
     * @param mixed[]|bool $data
     *
     * @return mixed[]|null
     *
     * @throws \Kafka\Exception
     */
    public function send($data = true): ?array
    {
        $ret = null;
        if (is_array($data)) {
            $ret = $this->process->send($data);
        }

        return $ret;
    }

    public function syncMeta(): void
    {
        $this->process->syncMeta();
    }

    public function success(callable $success): void
    {
        if ($this->process instanceof SyncProcess) {
            throw new Exception('Success callback can only be configured for asynchronous process');
        }

        $this->process->setSuccess($success);
    }

    public function error(callable $error): void
    {
        if ($this->process instanceof SyncProcess) {
            throw new Exception('Error callback can only be configured for asynchronous process');
        }

        $this->process->setError($error);
    }
}
