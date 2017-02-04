<?php
require_once __DIR__.'/../../vendor/autoload.php';

use G\Rabbit\Builder;

$server = [
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'pass' => 'guest',
];
$exchange = Builder::exchange('process.log', $server);

$exchange->emit("xxx.log", "aaaa");
$exchange->emit("xxx.log", ["11", "aaaa"]);
$exchange->emit("yyy.log", "aaaa");