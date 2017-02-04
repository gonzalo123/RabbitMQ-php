<?php
namespace G\Rabbit;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Exchange
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

    public function emit($routingKey, $data = null)
    {
        $connection = $this->createConnection();
        $channel = $connection->channel();
        $conf = $this->conf['exchange'];
        $channel->exchange_declare($this->name, 'topic', $conf['passive'], $conf['durable'], $conf['auto_delete'],
            $conf['internal'], $conf['nowait']);

        $msg = new AMQPMessage(json_encode($data), [
            'delivery_mode' => 2, # make message persistent
        ]);
        $channel->basic_publish($msg, $this->name, $routingKey);
        $channel->close();
        $connection->close();
    }

    public function receive($bindingKey, callable $callback)
    {
        $connection = $this->createConnection();
        $channel = $connection->channel();
        $conf = $this->conf['exchange'];
        $channel->exchange_declare($this->name, 'topic', $conf['passive'], $conf['durable'], $conf['auto_delete'],
            $conf['internal'], $conf['nowait']);

        $queueConf = $this->conf['queue'];
        list($queue_name, ,) = $channel->queue_declare("", $queueConf['passive'], $queueConf['durable'],
            $queueConf['exclusive'], $queueConf['auto_delete'], $queueConf['nowait']);

        $channel->queue_bind($queue_name, $this->name, $bindingKey);

        $consumerConf = $this->conf['consumer'];
        $channel->basic_consume($queue_name, '', $consumerConf['no_local'], $consumerConf['no_ack'],
            $consumerConf['exclusive'], $consumerConf['nowait'],
            function ($msg) use ($callback) {
                call_user_func($callback, $msg->delivery_info['routing_key'], json_decode($msg->body, true));
                $now = new \DateTime();
                echo '['.$now->format('d/m/Y H:i:s').'] '.$this->name.':'.$msg->delivery_info['routing_key'].'::', $msg->body, "\n";
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            });

        $now = new \DateTime();
        echo '['.$now->format('d/m/Y H:i:s')."] Exchange '{$this->name}' initialized \n";

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
