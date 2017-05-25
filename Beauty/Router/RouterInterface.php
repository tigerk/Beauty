<?php

namespace Beauty\Router;


interface RouterInterface
{
    /**
     * current request route
     *
     * @return mixed
     */
    public function getCurrentRoute();

    /**
     * get match route
     *
     * @param $httpMethod
     * @param $resourceUri
     * @param bool|false $reload
     * @return mixed
     */
    public function getMatchedRoutes($httpMethod, $resourceUri, $reload = false);

    /**
     * @param $mapping
     * @param $method
     * @return mixed
     */
    public function map($mapping, $method);

}