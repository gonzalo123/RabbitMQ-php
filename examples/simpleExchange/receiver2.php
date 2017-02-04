<?php
require_once __DIR__.'/../../vendor/autoload.php';

use G\Rabbit\Builder;

$server = [
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'pass' => 'guest',
];

Builder::exchange('process.log', $server)->receive("*.log", function ($routingKey, $data) {
    error_log($routingKey." - ".json_encode($data));
});
