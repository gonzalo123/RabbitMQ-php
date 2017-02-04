<?php
require_once __DIR__.'/../../vendor/autoload.php';

use G\Rabbit\Builder;

$server = [
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'pass' => 'guest',
];
Builder::queue('queue', $server)->receive(function ($data, $queueName) use ($server) {
    Builder::exchange('process.log', $server)->emit("exchange.start", $queueName);
    error_log(json_encode($data));
    Builder::exchange('process.log', $server)->emit("exchange.finish", $queueName);
});
