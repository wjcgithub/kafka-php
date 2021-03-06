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

Swoole\Coroutine\run(function () {
//    startXhprof();
// Create the logger
    $logger = new Logger('my_logger');
// Now add some handlers
    $logger->pushHandler(new StdoutHandler());
    /**
     * @var ProducerConfig $config
     */
    $config = ProducerConfig::getInstance();
    $config->setMetadataRefreshIntervalMs(10000);
    $config->setMetadataBrokerList('10.90.71.159:9092');
    $config->setBrokerVersion('0.10.2.1');
    $config->setRequiredAck(1);
    $config->setIsAsyn(true);
    $config->setProduceInterval(500);
    $config->setTimeout(5);
    $config->setMetadataRequestTimeoutMs(2000);

    $producer = new Producer();
    $producer->setLogger($logger);

    $succ = [];
    for ($i = 0; $i < 1; $i++) {
        Coroutine::create(function () use($producer, $i, &$succ) {
            echo "start \r\n";
            $result = $producer->send([
                [
                    'topic' => 'wjc_kafka_coroutine_test',
                    'value' => 'test1....message.',
                    'key' => '',
                ]
            ]);
            // print_r($result);
            $succ[$i] = $i;
            echo "$i ok, current ".count($succ)." \r\n";
        });
        echo "start========$i=======\r\n";
    }

    \Swoole\Event::wait();
//     endXhprof();
});
