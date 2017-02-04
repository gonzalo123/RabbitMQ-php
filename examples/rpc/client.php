<?php
require_once __DIR__.'/../../vendor/autoload.php';

use G\Rabbit\Builder;

$server = [
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'pass' => 'guest',
];
echo Builder::rpc('rpc.hello', $server)->call("Gonzalo", "Ayuso");