<?php
namespace G\Rabbit;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Queue
{
    private $name;
    private $conf;

    public function __construct($name, $conf)
    {
        $this->name = $name;
        $this->conf = $conf;
    }

    private function createConnection()
    {
        $server = $this->conf['server'];

        return new AMQPStreamConnection($server['host'], $server['port'], $server['user'], $server['pass']);
    }

    private function declareQueue($channel)
    {
        $conf = $this->conf['queue'];
        $channel->queue_declare($this->name, $conf['passive'], $conf['durable'], $conf['exclusive'],
            $conf['auto_delete'], $conf['nowait']);
    }

    public function emit($data = null)
    {
        $connection = $this->createConnection();
        $channel = $connection->channel();
        $this->declareQueue($channel);

        $msg = new AMQPMessage(json_encode($data),
            ['delivery_mode' => 2] # make message persistent
        );

        $channel->basic_publish($msg, '', $this->name);

        $channel->close();
        $connection->close();
    }

    public function receive(callable $callback)
    {
        $connection = $this->createConnection();
        $channel = $connection->channel();

        $this->declareQueue($channel);
        $consumer = $this->conf['consumer'];

        if ($consumer['no_ack'] === false) {
            $channel->basic_qos(null, 1, null);
        }

        $channel->basic_consume($this->name, '', $consumer['no_local'], $consumer['no_ack'], $consumer['exclusive'],
            $consumer['nowait'],
            function ($msg) use ($callback) {
                call_user_func($callback, json_decode($msg->body, true), $this->name);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                $now = new \DateTime();
                echo '['.$now->format('d/m/Y H:i:s')."] {$this->name}::".$msg->body, "\n";
            });

        $now = new \DateTime();
        echo '['.$now->format('d/m/Y H:i:s')."] Queue '{$this->name}' initialized \n";

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}