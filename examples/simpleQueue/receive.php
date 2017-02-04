<?php
require_once __DIR__.'/../../vendor/autoload.php';

use G\Rabbit\Builder;

$server = [
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'pass' => 'guest',
];

Builder::queue('queue.backend', $server)->receive(function ($data) {
    error_log(json_encode($data));
});
