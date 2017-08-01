<?php

/**
 * 使用Hashring实现哈希分布。
 */

namespace Beauty\Cache;

use Beauty\App;
use Beauty\Lib\HashRing;
use Memcached;

class MemcacheClient
{
    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected      $prefix;
    private        $hashring;
    private static $connections;
    private static $_instance;

    function __construct()
    {
        $this->config   = App::config()->get('cache');
        $this->prefix   = $this->config['memcached']['prefix'];
        $this->hashring = new HashRing();
        $this->hashring->add($this->config['memcached']['hosts']);
    }

    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new MemcacheClient();
        }

        return self::$_instance;
    }

    /**
     * 获取memcache服务器
     *
     * @param $key
     * @return mixed
     */
    private function connect($key)
    {
        $server = $this->hashring->get($key);

        if (self::$connections[$server]) {
            return self::$connections[$server];
        }

        $memcached = new Memcached();
        $memcached->addServer($server['host'], $server['port'], $server['weight']);

        // check memcache connection
        if ($memcached->getVersion() === false) {
            throw new \RuntimeException("Could not establish Memcached connection.");
        }

        self::$connections[$server] = $memcached;

        return self::$connections[$server];
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string $key
     * @param  callable $cb
     * @return mixed
     */
    public function get($key, $default = NULL)
    {
        $memcached = $this->connect($key);

        $value = $memcached->get($this->prefix . $key);

        if ($memcached->getResultCode() == 0) {
            if (!$value && $default != NULL) {
                if (is_callable($default)) {
                    return call_user_func($default);
                } else {
                    return $default;
                }
            }

            return $value;
        }
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  int $seconds
     * @return void
     */
    public function put($key, $value, $seconds)
    {
        $this->connect($key)->set($this->prefix . $key, $value, $seconds);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @return mixed
     */
    public function increment($key, $value = 1)
    {
        return $this->connect($key)->increment($this->prefix . $key, $value);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @return mixed
     */
    public function decrement($key, $value = 1)
    {
        return $this->connect($key)->decrement($this->prefix . $key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->connect($key)->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string $key
     * @return void
     */
    public function forget($key)
    {
        $this->connect($key)->delete($this->prefix . $key);
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