<?php

/**
 * Redis
 */

namespace Beauty\Cache;

use Beauty\App;
use Beauty\Cache;
use Beauty\Lib\HashRing;

class RedisClient
{
    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected      $prefix;
    private        $hashring;
    private static $connections;

    function __construct()
    {
        $this->config   = App::config()->get('cache');
        $this->prefix   = $this->config['redis']['prefix'];
        $this->hashring = new HashRing();
        $this->hashring->add($this->config['redis']['hosts']);
    }

    /**
     * 获取redis服务器
     *
     * @param $key
     * @return mixed
     */
    public function connect($key)
    {
        $server = $this->hashring->get($key);

        if (self::$connections[$server]) {
            return self::$connections[$server];
        }

        $lobjredis = new \Redis();
        $status    = $lobjredis->connect($server['host'], $server['port']);

        // check memcache connection
        if ($status === false) {
            throw new \RuntimeException("Could not establish Redis connection.");
        }

        self::$connections[$server] = $lobjredis;

        return self::$connections[$server];
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}