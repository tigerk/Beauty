<?php

namespace Beauty\Http;

class Environment
{
    /**
     * Constructor, will parse an array for environment information if present
     * @param array $environment
     */
    public function __construct($environment = null)
    {
        if (!is_null($environment)) {
            $this->parse($environment);
        }
    }

    /**
     * Parse environment array
     *
     * This method will parse an environment array and add the data to
     * this collection
     *
     * @param  array $environment
     * @return void
     */
    public function parse(array $environment)
    {
        foreach ($environment as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * set value
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->$key = $value;
    }

    /**
     * get
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->$key;
    }
}