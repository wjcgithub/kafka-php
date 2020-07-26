<?php
declare(strict_types=1);
\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
require '../vendor/autoload.php';
date_default_timezone_set('PRC');

use Swoole\Coroutine;

Swoole\Coroutine\run(function () {
    Coroutine::create(function () {
        $ctx = Coroutine::getContext();
        $ctx->a = 11111;
        Coroutine::create(function () {
            $ctx = Coroutine::getContext();
            $ctx->b = 22222;
            echo $ctx->a . "=====\r\n";
        });
    });
});