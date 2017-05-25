<?php

/**
 * set access path
 */
define("BASE_PATH", __DIR__ . "/../");
define("APP_PATH", BASE_PATH . "app/");
define("CONFIG_PATH", APP_PATH . "config/");

/**
 * 定义路径方法
 */

if (!function_exists('base_path')) {
    function base_path()
    {
        return BASE_PATH;
    }
}

if (!function_exists('app_path')) {
    function app_path()
    {
        return APP_PATH;
    }
}

if (!function_exists('config_path')) {
    function config_path()
    {
        return CONFIG_PATH;
    }
}