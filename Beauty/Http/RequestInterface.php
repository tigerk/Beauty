<?php
/**
 * Created by PhpStorm.
 * User: kimhwawoon
 * Date: 14-7-14
 * Time: 下午3:47
 */

namespace Beauty\Http;


interface RequestInterface {
    /***** Header *****/

    public function getProtocolVersion();

    public function getMethod();

    public function setMethod($method);

    public function getUrl();

    public function setUrl($url);

    public function getHeaders();

    public function hasHeader($name);

    public function getHeader($name);

    public function setHeader($name, $value);

    public function setHeaders(array $headers);

    public function addHeader($name, $value);

    public function addHeaders(array $headers);

    public function removeHeader($name);

    /***** Body *****/

    public function getBody();

    public function setBody(\GuzzleHttp\Stream\StreamInterface $body);

    /***** Metadata *****/

    public function getScriptName();

    public function getPathInfo();

    public function getPath();

    public function getQueryString();

    public function isGet();

    public function get($key = null, $default = null);

    public function isPost();

    public function post($key = null, $default = null);

    public function isPut();

    public function put($key = null, $default = null);

    public function isPatch();

    public function patch($key = null, $default = null);

    public function isDelete();

    public function delete($key = null, $default = null);

    public function isHead();

    public function isOptions();

    public function isAjax();

    public function isXhr();

    public function isFormData();
} 