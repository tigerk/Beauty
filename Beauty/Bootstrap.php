<?php

/**
 * set handler function
 *
 * @param Throwable $e
 */
function handleException(Throwable $e)
{
    DLog::fatal(var_export($e, true), 0, []);
}

/**
 * set access path
 */
define("BASE_PATH", __DIR__ . "/../");
define("APP_PATH", BASE_PATH . "app/");
define("CONFIG_PATH", BASE_PATH . "config/");

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
        $env = parse_ini_file(BASE_PATH . ".env");

        return CONFIG_PATH . $env['environment'];
    }
}

if (!function_exists('environment')) {
    function environment()
    {
        $env = parse_ini_file(BASE_PATH . ".env");

        return $env['environment'];
    }
}