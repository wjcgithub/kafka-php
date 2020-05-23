<?php
declare(strict_types=1);
 \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
require '../vendor/autoload.php';
date_default_timezone_set('PRC');

use Kafka\Producer;
use Kafka\ProducerConfig;
use Monolog\Handler\StdoutHandler;
use Monolog\Logger;
use Swoole\Coroutine;

echo 111;

Swoole\Coroutine\run(function () {
// Create the logger
    $logger = new Logger('my_logger');
// Now add some handlers
    $logger->pushHandler(new StdoutHandler());

    /**
     * @var ProducerConfig $config
     */
    $config = ProducerConfig::getInstance();
    $config->setMetadataRefreshIntervalMs(10000);
    $config->setMetadataBrokerList('192.168.0.102:9092');
    $config->setBrokerVersion('0.10.2.1');
    $config->setRequiredAck(1);
    $config->setIsAsyn(true);
    $config->setProduceInterval(500);

    $producer = new Producer();
    // $producer->setLogger($logger);

    for ($i = 0; $i < 1; $i++) {
        Coroutine::create(function () use($producer) {
            echo "start \r\n";
            $result = $producer->send([
                [
                    'topic' => 'test',
                    'value' => 'test1....message.',
                    'key' => '',
                ],
            ]);
            echo "ok \r\n";
//            print_r($result);
        });
        echo "start========$i=======\r\n";
    }
});
