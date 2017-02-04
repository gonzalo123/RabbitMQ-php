<?php
namespace G\Rabbit;

class Builder
{
    private static $defaults = [
        'rpc'      => [
            'queue'    => [
                'passive'     => false,
                'durable'     => true,
                'exclusive'   => false,
                'auto_delete' => true,
                'nowait'      => false,
            ],
            'consumer' => [
                'no_local'  => false,
                'no_ack'    => false,
                'exclusive' => false,
                'nowait'    => false,
            ],
        ],
        'exchange' => [
            'exchange' => [
                'passive'     => false,
                'durable'     => true,
                'auto_delete' => true,
                'internal'    => false,
                'nowait'      => false,
            ],
            'queue'    => [
                'passive'     => false,
                'durable'     => true,
                'exclusive'   => false,
                'auto_delete' => true,
                'nowait'      => false,
            ],
            'consumer' => [
                'no_local'  => false,
                'no_ack'    => false,
                'exclusive' => false,
                'nowait'    => false,
            ],
        ],
        'queue'    => [
            'queue'    => [
                'passive'     => false,
                'durable'     => true,
                'exclusive'   => false,
                'auto_delete' => false,
                'nowait'      => false,
            ],
            'consumer' => [
                'no_local'  => false,
                'no_ack'    => false,
                'exclusive' => false,
                'nowait'    => false,
            ],
        ],
    ];

    public static function rpc($name, $server)
    {
        $conf = self::$defaults['rpc'];
        $conf['server'] = $server;

        return new RPC($name, $conf);
    }

    public static function exchange($name, $server)
    {
        $conf = self::$defaults['exchange'];
        $conf['server'] = $server;

        return new Exchange($name, $conf);
    }

    public static function queue($name, $server)
    {
        $conf = self::$defaults['queue'];
        $conf['server'] = $server;

        return new Queue($name, $conf);
    }
}