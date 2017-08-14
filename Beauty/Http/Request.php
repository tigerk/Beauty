<?php

namespace Beauty\Http;

class Request
{

    const METHOD_HEAD     = 'HEAD';
    const METHOD_GET      = 'GET';
    const METHOD_POST     = 'POST';
    const METHOD_PUT      = 'PUT';
    const METHOD_PATCH    = 'PATCH';
    const METHOD_DELETE   = 'DELETE';
    const METHOD_OPTIONS  = 'OPTIONS';
    const METHOD_OVERRIDE = '_METHOD';

    /**
     * Request paths (physical and virtual) cached per instance
     * @var array
     */
    protected $paths;

    protected $env;

    protected $segments;

    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    /**
     * Get HTTP method
     *
     * @return string
     * @api
     */
    public function getMethod()
    {
        // Get actual request method
        $method = $this->env->get('REQUEST_METHOD');

        return $method;
    }

    public function getPathInfo()
    {
        $paths = $this->parsePaths();

        return $paths['virtual'];
    }

    /**
     * Get query string
     *
     * @return string
     * @api
     */
    public function getQueryString()
    {
        return $this->env->get('QUERY_STRING', '');
    }

    /**
     * Parse the physical and virtual paths from the request URI
     *
     * @return array
     */
    protected function parsePaths()
    {
        $this->paths             = array();
        $this->paths['physical'] = $_SERVER['SCRIPT_NAME'];


        if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
            $this->paths['virtual'] = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
        } else {
            $this->paths['virtual'] = $_SERVER['REQUEST_URI'];
        }

        return $this->paths;
    }

    protected function parseSegment()
    {
        $segments = explode('/', $this->getPathInfo());

        $this->segments = array_values(array_filter($segments, function ($v) {
            return $v != '';
        }));

        return $this->segments;
    }

    public function segments()
    {
        if ($this->segments) {
            return $this->segments;
        }

        return $this->parseSegment();
    }

    public function segment($index, $default = null)
    {
        $segments = $this->segments();

        if (array_key_exists($index, $segments)) {
            return $segments[$index - 1];
        }

        return $default;
    }
}