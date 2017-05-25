<?php

namespace Beauty\Router;

class Router implements RouterInterface
{
    /**
     * The current (most recently dispatched) route
     */
    protected $currentRoute;

    /**
     * All route json object, numerically indexed
     */
    protected $routes;

    /**
     * Add a route
     * ps. here, not use object, for simple
     *
     * @param $mapping
     * @param $method
     * @return null
     */
    public function map($mapping, $method)
    {
        $pattern  = array_shift($mapping);
        $callable = array_pop($mapping);

        $this->routes[$pattern] = [
            "method"   => $method,
            "callable" => $callable
        ];
    }

    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    public function getCurrentRouteCallable()
    {
        return $this->currentRoute['callable'];
    }

    /**
     * check http method is allowed.
     *
     * @param $route
     * @param $httpMethod
     * @return bool
     */
    public function supportsHttpMethod($route, $httpMethod)
    {
        return $route['method'] == $httpMethod ? true : false;
    }

    /**
     * get matched route
     *
     * @param $httpMethod
     * @param $resourceUri
     * @param bool|false $save
     * @return null
     */
    public function getMatchedRoutes($httpMethod, $resourceUri, $save = false)
    {
        if (array_key_exists($resourceUri, $this->routes)) {
            $this->currentRoute = $this->routes[$resourceUri];
            if ($this->supportsHttpMethod($this->currentRoute, $httpMethod)) {
                return $this->currentRoute;
            }
        }

        return null;
    }
}