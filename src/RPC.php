<?php
namespace G\Rabbit;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RPC
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

    public function call()
    {
        $params   = (array)func_get_args();
        $response = null;
        $corr_id  = uniqid();

        $connection = $this->createConnection();
        $channel    = $connection->channel();

        $queueConf = $this->conf['queue'];
        list($callback_queue, ,) = $channel->queue_declare("", $queueConf['passive'], $queueConf['durable'], $queueConf['exclusive'], $queueConf['auto_delete'], $queueConf['nowait']);

        $consumerConf = $this->conf['consumer'];
        $channel->basic_consume($callback_queue, '', $consumerConf['no_local'], $consumerConf['no_ack'], $consumerConf['exclusive'], $consumerConf['nowait'], function ($rep) use (&$corr_id, &$response) {
            if ($rep->get('correlation_id') == $corr_id) {
                $response = $rep->body;
            }

        });

        $msg = new AMQPMessage(json_encode($params), [
            'correlation_id' => $corr_id,
            'reply_to'       => $callback_queue,
        ]);
        $channel->basic_publish($msg, '', $this->name);
        while (!$response) {
            $channel->wait();
        }

        return json_decode($response, true);
    }

    public function server(callable $callback)
    {
        $connection = $this->createConnection();
        $channel    = $connection->channel();

        $queueConf = $this->conf['queue'];
        $channel->queue_declare($this->name, $queueConf['passive'], $queueConf['durable'], $queueConf['exclusive'], $queueConf['auto_delete'], $queueConf['nowait']);

        $now = new \DateTime();
        echo '[' . $now->format('d/m/Y H:i:s') . "] RPC server '{$this->name}' initialized \n";

        $channel->basic_qos(null, 1, null);
        $consumerConf = $this->conf['consumer'];
        $channel->basic_consume($this->name, '', $consumerConf['no_local'], $consumerConf['no_ack'], $consumerConf['exclusive'], $consumerConf['nowait'], function ($req) use ($callback) {
            $response = json_encode(call_user_func_array($callback, json_decode($req->body, true)));

            $msg = new AMQPMessage($response, [
                'correlation_id' => $req->get('correlation_id'),
                'delivery_mode'  => 2, # make message persistent
            ]);

            $req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));
            $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
            $now = new \DateTime();
            echo '[' . $now->format('d/m/Y H:i:s') . '] ' . $this->name . ":: req => '{$req->body}' response=> '{$response}'\n";
        });

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
