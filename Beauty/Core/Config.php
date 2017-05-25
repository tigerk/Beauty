<?php

namespace Beauty;

/**
 * 获取配置参数
 * @package Beauty
 */

class Config implements \ArrayAccess
{
    protected $path;

    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = array();

    public function __construct()
    {
        $this->path = config_path();
    }


    /**
     * Get the returned value of a file.
     *
     * @param  string $path
     * @return mixed
     *
     * @throws \FileNotFoundException
     */
    public function getRequire($path)
    {
        if (is_file($path)) {
            return require $path;
        }

        throw new \FileNotFoundException("File does not exist at path {$path}");
    }

    public function has($key)
    {
        return !is_null($this->get($key));
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        list($group, $item) = $this->parseKey($key);

        $this->load($group);

        if (is_null($item)) {
            return $this->items[$group];
        }

        return isset($this->items[$group][$item]) ? $this->items[$group][$item] : $default;
    }

    /**
     * Set a given configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        list($group, $item) = $this->parseKey($key);

        $this->load($group);

        $this->items[$group][$item] = $value;
    }

    /**
     * parse by dot
     *
     * @param $key
     * @return array
     */
    public function parseKey($key)
    {
        $segments = explode('.', $key);

        if (count($segments) == 1) {
            return array($segments[0], null);
        }

        return $segments;
    }

    public function load($group)
    {
        if (isset($this->items[$group])) {
            return;
        }

        $this->items[$group] = $this->getRequire($this->path . '/' . $group . '.php');

    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $key <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $key <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($key)
    {
        $this->get($key);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $key <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->get($key, $value);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $key <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}