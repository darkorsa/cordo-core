<?php

declare(strict_types=1);

namespace Cordo\Core\Infractructure\Persistance\Bernard\Driver;

use Bernard\Driver;
use InvalidArgumentException;
use Bernard\Driver\Redis\Driver as RedisDriver;
use Bernard\Driver\FlatFile\Driver as FileDriver;

class QueueDriverFactory
{
    public static function factory(array $config): Driver
    {
        switch ($config['driver']) {
            case 'redis':
                return self::redisDriver($config['drivers']['redis']);
            case 'file':
                return new FileDriver($config['drivers']['file']['path']);
            default:
                throw new InvalidArgumentException("Unknown mailer driver: " . $config['driver']);
        }
    }

    private static function redisDriver(array $config): RedisDriver
    {
        $redis = new \Redis();
        $redis->connect($config['server'], (int) $config['port']);
        $redis->setOption(\Redis::OPT_PREFIX, $config['prefix']);

        if ($config['secret']) {
            $redis->auth($config['secret']);
        }

        return new RedisDriver($redis);
    }
}
