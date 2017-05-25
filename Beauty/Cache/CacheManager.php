<?php
/**
 * Created by PhpStorm.
 * User: tigerkim
 * Date: 15/4/28
 * Time: 22:40
 */

namespace Beauty\Cache;

class CacheManager
{
    protected $config;

    protected $store;

    function __construct()
    {
        $this->config = \Beauty\App::config()->get('cache');
    }

    /**
     * Create an instance of the Memcached cache driver.
     *
     */
    protected function createMemcachedDriver()
    {
        $servers = $this->config['memcached'];

        $connector = new MemcachedConnector();

        $memcached = $connector->connect($servers);

        return new MemcachedStore($memcached, $this->getPrefix());
    }

    /**
     * Get the cache "prefix" value.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->config['prefix'];
    }

    /**
     * Set the cache "prefix" value.
     *
     * @param  string $name
     * @return void
     */
    public function setPrefix($name)
    {
        $this->app['config']['cache.prefix'] = $name;
    }

    /**
     * Create a new cache repository with the given implementation.
     *
     */
    protected function repository(StoreInterface $store)
    {
        return new Repository($store);
    }

    /**
     * Get the default cache driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['cache.driver'];
    }

    /**
     * Set the default cache driver name.
     *
     * @param  string $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['cache.driver'] = $name;
    }

}
