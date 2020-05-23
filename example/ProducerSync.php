<?php

declare(strict_types=1);
// \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
require '../vendor/autoload.php';
date_default_timezone_set('PRC');

use Infection\Process\Runner\Result;
use Kafka\Producer;
use Kafka\ProducerConfig;
use Monolog\Handler\StdoutHandler;
use Monolog\Logger;


\Swoole\Coroutine::create(function () {
    $logger = new Logger('my_logger');
    $logger->pushHandler(new StdoutHandler());

    $config = ProducerConfig::getInstance();
    $config->setMetadataRefreshIntervalMs(10000);
    $config->setMetadataBrokerList('10.90.71.159:9092');
    $config->setBrokerVersion('0.10.2.1');
    $config->setRequiredAck(1);
    $config->setIsAsyn(true);
    $config->setProduceInterval(500);

    $producer = new Producer();
    $producer->setLogger($logger);
    for ($i = 0; $i < 1000; $i++) {
        echo "$i start\r\n";
        $result = $producer->send([
            [
                'topic' => 'wjc_kafka_coroutine_test',
                'value' => 'test1....message.',
                'key' => '',
            ],
        ]);
        echo "$i end\r\n";
    }

});